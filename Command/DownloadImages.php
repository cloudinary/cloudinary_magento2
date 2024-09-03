<?php

namespace Cloudinary\Cloudinary\Command;

use Cloudinary\Cloudinary\Model\BatchDownloader;
use Cloudinary\Cloudinary\Model\Configuration;
use Cloudinary\Cloudinary\Model\Logger\OutputLogger;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
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
    const FORCE = 'force';
    const ENV = 'env';
    /**#@- */

    const OVERRIDE_CONFIRM_MESSAGE = "<question>Are you sure you want to override local files (y/n)[n]?</question>";

    private $_override = false;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var OutputLogger
     */
    private $outputLogger;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var BatchDownloader
     */
    private $batchDownloader;

    /**
     * @method __construct
     * @param  ObjectManagerInterface $objectManager
     * @param  OutputLogger    $outputLogger
     * @param  Registry        $coreRegistry
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        OutputLogger $outputLogger,
        Registry $coreRegistry
    ) {
        parent::__construct('cloudinary:download:all');

        $this->objectManager = $objectManager;
        $this->outputLogger = $outputLogger;
        $this->coreRegistry = $coreRegistry;
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
            new InputOption(
                self::FORCE,
                '-f',
                InputOption::VALUE_NONE,
                'Force download even if Cloudinary is disabled'
            ),
            new InputOption(
                self::ENV,
                '-e',
                InputOption::VALUE_OPTIONAL,
                'Cloudinary environment variable that will be used during the process',
                null
            ),
        ]);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return init
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {
            $this->batchDownloader = $this->objectManager
                ->get(\Cloudinary\Cloudinary\Model\BatchDownloader::class);

            if ($input->getOption(self::OVERRIDE) && $this->confirmQuestion(self::OVERRIDE_CONFIRM_MESSAGE, $input, $output)) {
                $this->_override = true;
            }
            if (($env = $input->getOption(self::ENV))) {
                $this->coreRegistry->register(Configuration::CONFIG_PATH_ENVIRONMENT_VARIABLE, $env);
            }
            if ($input->getOption(self::FORCE)) {
                $this->coreRegistry->register(Configuration::CONFIG_PATH_ENABLED, true);
            }
            $this->outputLogger->setOutput($output);
            $this->batchDownloader->downloadUnsynchronisedImages($this->outputLogger, $this->_override);

            return 1;

        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return 0;
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
