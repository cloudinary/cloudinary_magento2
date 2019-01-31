<?php

namespace Cloudinary\Cloudinary\Command;

use Cloudinary\Cloudinary\Model\BatchDownloader;
use Cloudinary\Cloudinary\Model\Logger\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadImages extends Command
{
    /**
     * @var BatchDownloader
     */
    private $batchDownloader;

    /**
     * @var OutputLogger
     */
    private $outputLogger;

    /**
     * @param BatchDownloader $batchDownloader
     */
    public function __construct(BatchDownloader $batchDownloader, OutputLogger $outputLogger)
    {
        parent::__construct('cloudinary:download:all');

        $this->batchDownloader = $batchDownloader;
        $this->outputLogger = $outputLogger;
    }

    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('cloudinary:download:all');
        $this->setDescription('Download images from Cloudinary to the local pub/media dir');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->outputLogger->setOutput($output);
            $this->batchDownloader->downloadUnsynchronisedImages($this->outputLogger);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
