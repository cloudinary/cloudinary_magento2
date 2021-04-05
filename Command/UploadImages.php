<?php

namespace Cloudinary\Cloudinary\Command;

use Cloudinary\Cloudinary\Model\BatchUploader;
use Cloudinary\Cloudinary\Model\Configuration;
use Cloudinary\Cloudinary\Model\Logger\OutputLogger;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UploadImages extends Command
{
    /**#@+
     * Keys and shortcuts for input arguments and options
     */
    const FORCE = 'force';
    const ENV = 'env';
    /**#@- */

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
     * @var BatchUploader
     */
    private $batchUploader;

    /**
     * @method __construct
     * @param  ObjectManagerInterface $objectManager
     * @param  OutputLogger           $outputLogger
     * @param  Registry               $coreRegistry
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        OutputLogger $outputLogger,
        Registry $coreRegistry
    ) {
        parent::__construct('cloudinary:upload:all');

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
        $this->setName('cloudinary:upload:all');
        $this->setDescription('Upload unsynchronised images');
        $this->setDefinition([
            new InputOption(
                self::FORCE,
                '-f',
                InputOption::VALUE_NONE,
                'Force upload even if Cloudinary is disabled'
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
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->batchUploader = $this->objectManager
                ->get(\Cloudinary\Cloudinary\Model\BatchUploader::class);

            if (($env = $input->getOption(self::ENV))) {
                $this->coreRegistry->register(Configuration::CONFIG_PATH_ENVIRONMENT_VARIABLE, $env);
            }
            if ($input->getOption(self::FORCE)) {
                $this->coreRegistry->register(Configuration::CONFIG_PATH_ENABLED, true);
            }
            $this->outputLogger->setOutput($output);
            $this->batchUploader->uploadUnsynchronisedImages($this->outputLogger);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
