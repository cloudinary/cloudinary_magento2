<?php

namespace Cloudinary\Cloudinary\Model\Observer;

use Cloudinary\Cloudinary\Core\AutoUploadMapping\AutoUploadConfigurationInterface;
use Cloudinary\Cloudinary\Core\AutoUploadMapping\RequestProcessor;
use Cloudinary\Cloudinary\Model\AutoUploadMapping\AutoUploadConfiguration;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class Configuration implements ObserverInterface
{
    const AUTO_UPLOAD_SETUP_FAIL_MESSAGE = 'Error. Unable to setup auto upload mapping.';

    /**
     * @var RequestProcessor
     */
    protected $requestProcessor;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Cloudinary\Cloudinary\Model\Configuration
     */
    protected $configuration;

    /**
     * @var AutoUploadConfiguration
     */
    protected $autoUploadConfiguration;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    protected $changedPaths = [];

    /**
     * @param RequestProcessor $requestProcessor
     * @param ManagerInterface $messageManager
     * @param \Cloudinary\Cloudinary\Model\Configuration $configuration
     * @param AutoUploadConfiguration $autoUploadConfiguration
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        RequestProcessor $requestProcessor,
        ManagerInterface $messageManager,
        \Cloudinary\Cloudinary\Model\Configuration $configuration,
        AutoUploadConfiguration $autoUploadConfiguration,
        TypeListInterface $cacheTypeList
    ) {
        $this->requestProcessor = $requestProcessor;
        $this->messageManager = $messageManager;
        $this->configuration = $configuration;
        $this->autoUploadConfiguration = $autoUploadConfiguration;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        //Clear config cache if needed
        $this->changedPaths = (array) $observer->getEvent()->getChangedPaths();
        if (count(array_intersect($this->changedPaths, [
            \Cloudinary\Cloudinary\Model\Configuration::CONFIG_PATH_ENABLED,
            \Cloudinary\Cloudinary\Model\Configuration::CONFIG_PATH_ENVIRONMENT_VARIABLE,
            \Cloudinary\Cloudinary\Model\AutoUploadMapping\AutoUploadConfiguration::REQUEST_PATH
        ])) > 0) {
            $this->autoUploadConfiguration->setState(AutoUploadConfigurationInterface::INACTIVE);
            $this->cleanConfigCache();
        }

        if (!$this->requestProcessor->handle('media', $this->configuration->getMediaBaseUrl())) {
            $this->messageManager->addErrorMessage(self::AUTO_UPLOAD_SETUP_FAIL_MESSAGE);
        }
    }

    protected function cleanConfigCache()
    {
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        return $this;
    }
}
