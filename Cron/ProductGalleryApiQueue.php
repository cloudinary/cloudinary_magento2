<?php

namespace Cloudinary\Cloudinary\Cron;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Model\Api\ProductGalleryManagement;
use Cloudinary\Cloudinary\Model\ProductGalleryApiQueueFactory;
use Cloudinary\Cloudinary\Model\ProductVideoFactory;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Notification\NotifierInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductGalleryApiQueue
{
    /**
     * @var array
     */
    private $adminNotificationErrors = [];

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var ProductGalleryManagement
     */
    private $cldProductGalleryManagement;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @param ProductVideoFactory
     */
    private $productVideoFactory;

    /**
     * @var ProductGalleryApiQueueFactory
     */
    private $productGalleryApiQueueFactory;

    /**
     * @var NotifierInterface
     */
    private $notifierPool;

    /**
     * @method __construct
     * @param  ConfigurationInterface        $configuration
     * @param  ProductGalleryManagement      $cldProductGalleryManagement
     * @param  JsonHelper                    $jsonHelper
     * @param  ProductVideoFactory           $productVideoFactory
     * @param  ProductGalleryApiQueueFactory $productGalleryApiQueueFactory
     * @param  NotifierInterface             $notifierPool
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ProductGalleryManagement $cldProductGalleryManagement,
        JsonHelper $jsonHelper,
        ProductVideoFactory $productVideoFactory,
        ProductGalleryApiQueueFactory $productGalleryApiQueueFactory,
        NotifierInterface $notifierPool
    ) {
        $this->configuration = $configuration;
        $this->cldProductGalleryManagement = $cldProductGalleryManagement;
        $this->jsonHelper = $jsonHelper;
        $this->productVideoFactory = $productVideoFactory;
        $this->productGalleryApiQueueFactory = $productGalleryApiQueueFactory;
        $this->notifierPool = $notifierPool;
    }

    public function execute()
    {
        if ($this->configuration->isEnabled() && $this->configuration->isEnabledProductgalleryApiQueue()) {
            try {
                $queuedItems = $this->productGalleryApiQueueFactory->create()->getCollection()
                    ->addFieldToFilter("success", 0)
                    ->addFieldToFilter("tryouts", ['lt' => $this->configuration->getProductgalleryApiQueueMaxTryouts()])
                    ->setOrder('created_at', 'asc');
                if (($_limit = $this->configuration->getProductgalleryApiQueueLimit())) {
                    $queuedItems->setPageSize($_limit);
                }

                foreach ($queuedItems as $item) {
                    try {
                        $fullItemData = $this->jsonHelper->jsonDecode($item->getFullItemData());
                        $this->processOutput("ProductGalleryApiQueue::execute() - Processing item ID: {$item->getId()} ...", "debug", ['full_item_data' => $fullItemData]);
                        $item->setTryouts($item->getTryouts() + 1);
                        $this->cldProductGalleryManagement->addGalleryItem(
                            $fullItemData["url"],
                            $fullItemData["sku"],
                            $fullItemData["publicId"],
                            $fullItemData["roles"],
                            $fullItemData["label"],
                            $fullItemData["disabled"]
                        );
                        $item->setSuccess(1);
                        $item->setSuccessAt(date('Y-m-d H:i:s'));
                        $item->setMessage('success');
                        $item->setHasErrors(0);
                    } catch (\Exception $e) {
                        $item->setSuccess(0);
                        $item->setMessage("[ERROR]\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
                        $item->setHasErrors(1);

                        $this->processOutput("ProductGalleryApiQueue::execute() - Exception during product-gallery API queued item processing: " . $e->getMessage(), 'error', ['trace' => $e->getTraceAsString(), 'queued_item' => $item->getData()]);
                        if (!($this->output instanceof OutputInterface) && $item->getTryouts() >= $this->configuration->getProductgalleryApiQueueMaxTryouts() && count($this->adminNotificationErrors) < 7) {
                            $this->adminNotificationErrors[] = [
                                "message" => $e->getMessage(),
                                "tryouts" => $item->getTryouts(),
                                "item_data" => $fullItemData
                            ];
                        }
                    }

                    $item->save();
                    $this->processOutput("ProductGalleryApiQueue::execute() - Processing item ID: {$item->getId()} - Done.", "debug");
                }
            } catch (\Exception $e) {
                $this->processOutput("ProductGalleryApiQueue::execute() - Exception during product-gallery API queue processing: " . $e->getMessage(), 'error', ['trace' => $e->getTraceAsString()]);
                if (!($this->output instanceof OutputInterface) && $item->getTryouts() >= $this->configuration->getProductgalleryApiQueueMaxTryouts() && count($this->adminNotificationErrors) < 7) {
                    $this->adminNotificationErrors[] = [
                        "message" => $e->getMessage(),
                        "details" => $e->getTraceAsString()
                    ];
                }
            }
            if ($this->adminNotificationErrors) {
                $adminNotificationErrors = $this->jsonHelper->jsonEncode(array_slice($this->adminNotificationErrors, 0, 5));
                if (count($this->adminNotificationErrors) > 5) {
                    $adminNotificationErrors .= " ... [this message is too long, check the log for the rest] ";
                }
                $this->addAdminNotification("[Cloudinary] An error occurred during the background processing of the product-gallery API queue! *More detailes can be found on the Cloudinary log file (var/log/cloudinary_cloudinary.log)", $adminNotificationErrors, 'critical');
            }
        }

        return $this;
    }

    /**
     * @method setOutput
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @method getOutput
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Process output messages (log to system.log / output to terminal)
     * @method _processOutput
     * @return $this
     */
    protected function processOutput($message, $type = "info", $data = [])
    {
        if ($this->output instanceof OutputInterface) {
            //Output to terminal
            $outputType = ($type === "error") ? $type : "info";
            $this->output->writeln('<' . $outputType . '>' . json_encode($message) . '</' . $outputType . '>');
            if ($data) {
                $this->output->writeln('<comment>' . json_encode($data) . '</comment>');
            }
        } else {
            //Log to var/log/cloudinary_cloudinary.log
            $this->configuration->log($message, $data);
        }

        return $this;
    }

    private function addAdminNotification(string $title, $description = "", $type = 'critical')
    {
        $method = 'add' . ucfirst($type);
        $this->notifierPool->{$method}($title, $description);
        return $this;
    }
}
