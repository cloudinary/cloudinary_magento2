<?php

namespace Cloudinary\Cloudinary\Command;

use Cloudinary\Cloudinary\Cron\ProductGalleryApiQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductGalleryApiQueueProcess extends Command
{
    /**
     * @var ProductGalleryApiQueue
     */
    private $job;

    /**
     * @param ProductGalleryApiQueue $job
     */
    public function __construct(
        ProductGalleryApiQueue $job
    ) {
        parent::__construct('cloudinary:product-gallery-api-queue:process');

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
        return $this->job
            ->setOutput($output)
            ->execute();
    }
}
