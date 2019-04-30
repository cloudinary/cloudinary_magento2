<?php

namespace Cloudinary\Cloudinary\Command;

use Cloudinary\Cloudinary\Model\BatchDownloader;
use Cloudinary\Cloudinary\Model\Logger\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DownloadImages extends Command
{
    /**#@+
     * Keys and shortcuts for input arguments and options
     */
    const OVERRIDE = 'override';
    /**#@- */

    const OVERRIDE_CONFIRM_MESSAGE = "<question>Are you sure you want to override local files (y/n)[n]?</question>";

    private $_override = false;

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
        $this->setDefinition([
            new InputOption(
                self::OVERRIDE,
                '-o',
                InputOption::VALUE_NONE,
                'Override local images if already exists'
            ),
        ]);
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
            if ($input->getOption(self::OVERRIDE) && $this->confirmQuestion(self::OVERRIDE_CONFIRM_MESSAGE, $input, $output)) {
                $this->_override = true;
            }
            $this->outputLogger->setOutput($output);
            $this->batchDownloader->downloadUnsynchronisedImages($this->outputLogger, $this->_override);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }

    /**
     * @method confirmQuestion
     * @param string $message
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function confirmQuestion(string $message, InputInterface $input, OutputInterface $output)
    {
        $confirmationQuestion = new ConfirmationQuestion($message, false);
        return (bool)$this->getHelper('question')->ask($input, $output, $confirmationQuestion);
    }
}
