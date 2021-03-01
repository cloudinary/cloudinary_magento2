<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary;
use Cloudinary\Api;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Cloudinary\Core\Image\SynchronizationCheck;
use Cloudinary\Cloudinary\Core\SynchroniseAssetsRepositoryInterface;
use Cloudinary\Cloudinary\Model\Framework\File\Uploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Validator\AllowedProtocols;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Magento\MediaStorage\Model\ResourceModel\File\Storage\File as FileUtility;
use Symfony\Component\Console\Output\OutputInterface;

class BatchDownloader
{
    const API_REQUEST_MAX_RESULTS = 500;
    const API_REQUESTS_SLEEP_BEFORE_NEXT_CALL = 3; //Seconds
    const API_REQUEST_STOP_ON_REMAINING_RATE_LIMIT = 10;
    const WAIT_FOR_RATE_LIMIT_RESET_MESSAGE = "Cloudinary API - calls rate limit reached, sleeping until %s ...";
    const ERROR_MIGRATION_ALREADY_RUNNING = 'Cannot start download - a migration is in progress or was interrupted. If you are sure a migration is not running elsewhere run the cloudinary:migration:stop command before attempting another download.';
    const MESSAGE_DOWNLOAD_INTERRUPTED = 'Download manually stopped.';
    const DONE_MESSAGE = "Done :)";

    /**
     * @var ConfigurationInterface
     */
    private $_configuration;

    /**
     * @var ConfigurationBuilder
     */
    private $_configurationBuilder;

    /**
     * @var MigrationTask
     */
    private $_migrationTask;

    /**
     * @var Cloudinary\Api
     */
    private $_api;

    /**
     * @var DirectoryList
     */
    private $_directoryList;

    /**
     * @var Curl
     */
    private $_curl;

    /**
     * @var Filesystem
     */
    private $_fileSystem;
    /**
     * @var FileUtility
     */
    private $_fileUtility;

    /**
     * @var AllowedProtocols
     */
    private $_protocolValidator;

    /**
     * @var NotProtectedExtension
     */
    private $_extensionValidator;

    /**
     * @var SynchronizationCheck
     */
    private $_synchronizationChecker;

    /**
     * @var SynchroniseAssetsRepositoryInterface
     */
    private $_synchronisationRepository;

    /**
     * @var OutputInterface
     */
    private $_output;

    /**
     * @var bool
     */
    private $_override = false;

    private $_iteration = 0;
    private $_nextCursor = null;
    private $_rateLimitResetAt = null;
    private $_rateLimitAllowed = null;
    private $_rateLimitRemaining = null;

    /**
     * @method __construct
     * @param  ConfigurationInterface               $configuration
     * @param  ConfigurationBuilder                 $configurationBuilder
     * @param  MigrationTask                        $migrationTask
     * @param  Api                                  $api
     * @param  DirectoryList                        $directoryList
     * @param  Curl                                 $curl
     * @param  Filesystem                           $fileSystem
     * @param  FileUtility                          $fileUtility
     * @param  AllowedProtocols                     $protocolValidator
     * @param  NotProtectedExtension                $extensionValidator
     * @param  SynchronizationCheck                 $synchronizationChecker
     * @param  SynchroniseAssetsRepositoryInterface $synchronisationRepository
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        MigrationTask $migrationTask,
        Api $api,
        DirectoryList $directoryList,
        Curl $curl,
        Filesystem $fileSystem,
        FileUtility $fileUtility,
        AllowedProtocols $protocolValidator,
        NotProtectedExtension $extensionValidator,
        SynchronizationCheck $synchronizationChecker,
        SynchroniseAssetsRepositoryInterface $synchronisationRepository
    ) {
        $this->_configuration = $configuration;
        $this->_configurationBuilder = $configurationBuilder;
        $this->_migrationTask = $migrationTask;
        $this->_api = $api;
        $this->_directoryList = $directoryList;
        $this->_curl = $curl;
        $this->_fileSystem = $fileSystem;
        $this->_fileUtility = $fileUtility;
        $this->_protocolValidator = $protocolValidator;
        $this->_extensionValidator = $extensionValidator;
        $this->_synchronizationChecker = $synchronizationChecker;
        $this->_synchronisationRepository = $synchronisationRepository;
    }

    private function _authorise()
    {
        Cloudinary::config($this->_configurationBuilder->build());
        Cloudinary::$USER_PLATFORM = $this->_configuration->getUserPlatform();
    }

    /**
     * Find unsynchronised images and upload them to cloudinary
     *
     * @param  OutputInterface|null $output
     * @param  bool $override
     * @return bool
     * @throws \Exception
     */
    public function downloadUnsynchronisedImages(OutputInterface $output = null, $override = false)
    {
        if (!$this->_configuration->isEnabled(false)) {
            throw new \Exception("Cloudinary seems to be disabled. Please enable it first or pass -f in order to force it on the CLI");
        }
        if (!$this->_configuration->hasEnvironmentVariable()) {
            throw new \Exception("Cloudinary environment variable seems to be missing. Please configure it first or pass it to the command as `-e` in order to use on the CLI");
        }

        $this->_authorise();

        //= Config
        $this->_output = $output;
        $this->_override = (bool) $override;
        $baseMediaPath = $this->_directoryList->getPath(DirectoryList::MEDIA);
        $directoryInstance = $this->_fileSystem->getDirectoryWrite(DirectoryList::MEDIA);

        //= Checking migration lock / Start migration
        if (!$this->validateMigrationLock()) {
            return false;
        } else {
            $this->_migrationTask->start();
        }

        do {
            try {
                $this->_iteration++;
                $this->displayMessage('<comment>Iteration #' . $this->_iteration . '</comment>');

                $response = $this->getResources($this->_nextCursor);
                $response->setResourcesCount(count($response->getResources()));
                if ($response->getResourcesCount() > 0) {
                    $this->displayMessage('Found ' . $response->getResourcesCount() . ' image(s) to on this round. ' . (($response->getNextCursor()) ? '*More Rounds Expected*' : '*Last Round*'));
                    foreach ($response->getResources() as $i => &$resource) {
                        try {
                            //= Checking migration status
                            if ($this->_migrationTask->hasBeenStopped()) {
                                $this->displayMessage(self::MESSAGE_DOWNLOAD_INTERRUPTED);
                                return false;
                            }

                            //= Preparations & Validations
                            $resource = new DataObject($resource);
                            $this->displayMessage('<comment>= [Processing] Image ' . ($i+1) . '/' . $response->getResourcesCount() . '</comment>');
                            $resource->setPublicId(preg_replace('/^' . preg_quote(DirectoryList::MEDIA . DIRECTORY_SEPARATOR, '/') . '/', '', $resource->getPublicId()) . '.' . $resource->getFormat());
                            $this->displayMessage('<comment>=== [Processing] Public ID: ' . $resource->getPublicId() . '</comment>');
                            $remoteFileUrl = $resource->getSecureUrl();
                            $this->validateRemoteFile($remoteFileUrl);
                            $localFileName = $resource->getPublicId(); //Uploader::getCorrectFileName($resource->getPublicId());
                            $localFilePath = $baseMediaPath . DIRECTORY_SEPARATOR . $localFileName;
                            $this->validateRemoteFileExtensions($localFilePath);

                            //= Checking if already exists
                            $skipDownload = false;
                            $this->displayMessage('<comment>=== [Processing] Local path: ' . $localFilePath . '</comment>');
                            if ($directoryInstance->isFile($localFilePath)) {
                                $this->displayMessage('<comment>=== [Processing] Image already exists locally.</comment>');
                                if ($this->_override) {
                                    $this->displayMessage('<comment>=== [Processing] *Overriding*</comment>');
                                } else {
                                    $skipDownload = true;
                                }
                            }

                            //= Downloading image / Skipping
                            if ($skipDownload) {
                                $this->displayMessage('<comment>=== [Processing] Skipping download.</comment>');
                            } else {
                                $this->displayMessage('<comment>=== [Processing] Downloading image...</comment>');
                                $this->_curl->setConfig(['header' => false]);
                                $this->_curl->write('GET', $remoteFileUrl);
                                $image = $this->_curl->read();
                                if (empty($image)) {
                                    throw new LocalizedException(
                                        __('The preview image information is unavailable. Check your connection and try again.')
                                    );
                                }
                                $this->displayMessage('<comment>=== [Processing] Saving...</comment>');
                                $this->_fileUtility->saveFile($localFilePath, $image, $this->_override);
                                if (!$directoryInstance->isFile($localFilePath)) {
                                    throw new LocalizedException(__("Image not saved."));
                                }
                                $this->displayMessage('<comment>=== [Processing] Saved.</comment>');
                            }

                            //Flagging as syncronized
                            $resource->setImage(Image::fromPath($localFilePath, $localFileName));
                            if ($resource->getImage()->getRelativePath() && !$this->_synchronizationChecker->isSynchronized($resource->getImage()->getRelativePath())) {
                                $this->displayMessage('<comment>=== [Processing] Flagging As Syncronized...</comment>');
                                $this->_synchronisationRepository->saveAsSynchronized($resource->getImage()->getRelativePath());
                            } else {
                                $this->displayMessage('<comment>=== [Processing] Image already syncronized or auto-upload-mapping is enabled.</comment>');
                            }

                            //= Success
                            $this->displayMessage('<info>= [Success]</info>');
                        } catch (\Exception $e) {
                            $this->displayMessage('<error>= [Error] ' . $e->getMessage() . '</error>');
                            continue;
                        }
                    }
                } else {
                    $this->displayMessage('<info>' . self::DONE_MESSAGE . '</info>');
                    break;
                }
                if (($this->_nextCursor = $response->getNextCursor()) && (int)$this->_rateLimitRemaining <= self::API_REQUEST_STOP_ON_REMAINING_RATE_LIMIT) {
                    $this->displayMessage('<comment>' . sprintf(self::WAIT_FOR_RATE_LIMIT_RESET_MESSAGE, date('Y-m-d H:i:s', ($this->_rateLimitResetAt + 10))) . '</comment>');
                    time_sleep_until($this->_rateLimitResetAt + 10);
                }
                sleep(self::API_REQUESTS_SLEEP_BEFORE_NEXT_CALL); //Wait between each API call.
            } catch (\Exception $e) {
                $this->displayMessage('<error>' . $e->getMessage() . '</error>');
                break;
            }
        } while ($this->_nextCursor);

        $this->_migrationTask->stop();
        return true;
    }

    /**
     * @method getResources
     * @param  mixed       $nextCursor
     * @return DataObject
     */
    private function getResources($nextCursor = null)
    {
        $response = $this->_api->resources(
            [
            "resource_type" => 'image',
            "type" => "upload",
            "prefix" => DirectoryList::MEDIA . DIRECTORY_SEPARATOR,
            "max_results" => self::API_REQUEST_MAX_RESULTS,
            "next_cursor" => $nextCursor,
            ]
        );
        $this->_rateLimitResetAt = $response->rate_limit_reset_at;
        $this->_rateLimitAllowed = $response->rate_limit_allowed;
        $this->_rateLimitRemaining = $response->rate_limit_remaining;
        $response->resources = array_values($response['resources']);
        return new DataObject((array)$response);
    }

    /**
     * @param OutputInterface $output
     * @param string          $message
     */
    private function displayMessage($message)
    {
        if ($this->_output) {
            $this->_output->writeln($message);
        }
    }

    /**
     * @param  OutputInterface $output
     * @return bool
     */
    private function validateMigrationLock()
    {
        if ($this->_migrationTask->hasStarted()) {
            $this->displayMessage(self::ERROR_MIGRATION_ALREADY_RUNNING);
            return false;
        }

        return true;
    }

    /**
     * Validate remote file
     *
     * @param string $remoteFileUrl
     * @throws LocalizedException
     *
     * @return $this
     */
    private function validateRemoteFile($remoteFileUrl)
    {
        if (!$this->_protocolValidator->isValid($remoteFileUrl)) {
            throw new LocalizedException(
                __("Protocol isn't allowed")
            );
        }

        return $this;
    }

    /**
     * Invalidates files that have script extensions.
     *
     * @param string $filePath
     * @throws \Magento\Framework\Exception\ValidatorException
     * @return void
     */
    private function validateRemoteFileExtensions($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!$this->_extensionValidator->isValid($extension)) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Disallowed file type.'));
        }
    }
}
