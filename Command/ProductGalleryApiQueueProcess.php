<?php

namespace Cloudinary\Cloudinary\Command;

use Cloudinary\Cloudinary\Cron\ProductGalleryApiQueue;
use Magento\Framework\App\State as AppState;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductGalleryApiQueueProcess extends Command
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var ProductGalleryApiQueue
     */
    private $job;

    /**
     * @method __construct
     * @param  AppState               $appState
     * @param  ProductGalleryApiQueue $job
     */
    public function __construct(
        AppState $appState,
        ProductGalleryApiQueue $job
    ) {
        parent::__construct('cloudinary:product-gallery-api-queue:process');

        $this->appState = $appState;
        $this->job = $job;
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
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
        return $this->job
            ->setOutput($output)
            ->execute();
    }
}
