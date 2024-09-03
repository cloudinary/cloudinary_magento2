<?php

namespace Cloudinary\Cloudinary\Command;

use Magento\Framework\App\State as AppState;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductGalleryApiQueueProcess extends Command
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var $job
     */
    protected $job;

    /**
     * @method __construct
     * @param  ObjectManagerInterface $objectManager
     * @param  AppState               $appState
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        AppState $appState
    ) {
        parent::__construct('cloudinary:product-gallery-api-queue:process');

        $this->objectManager = $objectManager;
        $this->appState = $appState;
    }

    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('cloudinary:product-gallery-api-queue:process');
        $this->setDescription('Process queued items for product gallery API');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->job = $this->objectManager
            ->get(\Cloudinary\Cloudinary\Cron\ProductGalleryApiQueue::class);

        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
        return $this->job
            ->setOutput($output)
            ->execute();
    }
}
