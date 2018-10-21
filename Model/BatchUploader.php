<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Cloudinary\Cloudinary\Core\Image;
use Symfony\Component\Console\Output\OutputInterface;
use Cloudinary\Cloudinary\Core\AutoUploadMapping\AutoUploadConfigurationInterface;

class BatchUploader
{
    const ERROR_MIGRATION_ALREADY_RUNNING = 'Cannot start upload - a migration is in progress or was interrupted. If you are sure a migration is not running elsewhere run the cloudinary:upload:stop command before attempting another upload.';
    const ERROR_AUTO_UPLOAD1 = 'Manual migration is not required when auto upload mapping is enabled.';
    const ERROR_AUTO_UPLOAD2 = 'Please disable auto upload mapping and refresh the configuration cache ' .
                               'if you wish to perform a manual migration.';
    const MESSAGE_UPLOAD_IMAGE = 'Uploading image: %s';
    const MESSAGE_UPLOAD_FAILED = 'Could not upload image: %s - error: %s';
    const MESSAGE_UPLOAD_COMPLETE = 'Completed. Images processed: %s';
    const MESSAGE_UPLOAD_INTERRUPTED = 'Upload manually stopped.';

    /**
     * @var ImageRepository
     */
    private $imageRepository;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var MigrationTask
     */
    private $migrationTask;

    /**
     * @var CloudinaryImageManager
     */
    private $cloudinaryImageManager;

    /**
     * @var AutoUploadConfigurationInterface
     */
    private $autoUploadConfiguration;

    /**
     * @param ImageRepository $imageRepository
     * @param Configuration $configuration
     * @param MigrationTask $migrationTask
     * @param CloudinaryImageManager $cloudinaryImageManager
     */
    public function __construct(
        ImageRepository $imageRepository,
        Configuration $configuration,
        MigrationTask $migrationTask,
        CloudinaryImageManager $cloudinaryImageManager,
        AutoUploadConfigurationInterface $autoUploadConfiguration
    ) {
        $this->imageRepository = $imageRepository;
        $this->configuration = $configuration;
        $this->migrationTask = $migrationTask;
        $this->cloudinaryImageManager = $cloudinaryImageManager;
        $this->autoUploadConfiguration = $autoUploadConfiguration;
    }

    /**
     * Find unsynchronised images and upload them to cloudinary
     *
     * @param OutputInterface|null $output
     * @return bool
     * @throws \Exception
     */
    public function uploadUnsynchronisedImages(OutputInterface $output = null)
    {
        if (!$this->validateAutoUploadMapping($output) || !$this->validateMigrationLock($output)) {
            return false;
        }

        try {
            $this->migrationTask->start();

            $images = $this->imageRepository->findUnsynchronisedImages();
            foreach ($images as $image) {
                if ($this->migrationTask->hasBeenStopped()) {
                    $this->displayMessage($output, self::MESSAGE_UPLOAD_INTERRUPTED);
                    return false;
                }
                $this->uploadAndSynchronise($image, $output);
            }

            $this->migrationTask->stop();
            $this->displayMessage($output, sprintf(self::MESSAGE_UPLOAD_COMPLETE, count($images)));

            return true;

        } catch (\Exception $e) {
            $this->migrationTask->stop();
            throw $e;
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     */
    private function displayMessage(OutputInterface $output, $message)
    {
        if ($output) {
            $output->writeln($message);
        }
    }

    /**
     * @param Image $image
     * @param OutputInterface $output
     */
    private function uploadAndSynchronise(Image $image, OutputInterface $output)
    {
        try {
            $this->cloudinaryImageManager->uploadAndSynchronise($image, $output);
        } catch (\Exception $e) {
            $this->displayMessage($output, sprintf(self::MESSAGE_UPLOAD_FAILED, $image, $e->getMessage()));
        }
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    private function validateAutoUploadMapping(OutputInterface $output)
    {
        if ($this->autoUploadConfiguration->isActive()) {
            $this->displayMessage($output, self::ERROR_AUTO_UPLOAD1);
            $this->displayMessage($output, self::ERROR_AUTO_UPLOAD2);
            return false;
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    private function validateMigrationLock(OutputInterface $output)
    {
        if ($this->migrationTask->hasStarted()) {
            $this->displayMessage($output, self::ERROR_MIGRATION_ALREADY_RUNNING);
            return false;
        }

        return true;
    }
}
