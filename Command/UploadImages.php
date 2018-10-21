<?php

namespace Cloudinary\Cloudinary\Command;

use Cloudinary\Cloudinary\Model\BatchUploader;
use Cloudinary\Cloudinary\Model\Logger\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UploadImages extends Command
{
    /**
     * @var BatchUploader
     */
    private $batchUploader;

    /**
     * @var OutputLogger
     */
    private $outputLogger;

    /**
     * @param BatchUploader $batchUploader
     */
    public function __construct(BatchUploader $batchUploader, OutputLogger $outputLogger)
    {
        parent::__construct('cloudinary:upload:all');

        $this->batchUploader = $batchUploader;
        $this->outputLogger = $outputLogger;
    }

    /**
     * Configure the command
     * 
     * @return void
     */
    protected function configure()
    {
        $this->setName('cloudinary:upload:all');
        $this->setDescription('Upload unsynchronised images');
    }

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->outputLogger->setOutput($output);
            $this->batchUploader->uploadUnsynchronisedImages($this->outputLogger);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
