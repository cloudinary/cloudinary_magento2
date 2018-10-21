<?php

namespace Cloudinary\Cloudinary\Core\Migration;

use Cloudinary\Cloudinary\Core\Exception\FileExists;
use Cloudinary\Cloudinary\Core\Exception\MigrationError;
use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Cloudinary\Core\Image\Synchronizable;
use Cloudinary\Cloudinary\Core\ImageProvider;

class BatchUploader
{
    const MESSAGE_STATUS = 'Cloudinary migration: %s images migrated, %s failed';

    const MESSAGE_UPLOADED = 'Cloudinary migration: uploaded %s';

    const MESSAGE_UPLOAD_ERROR = 'Cloudinary migration: %s trying to upload %s';

    const MAXIMUM_RETRY_ATTEMPTS = 3;

    const MESSAGE_UPLOADED_EXISTS = 'Cloudinary migration: %s exists - tagged as synchronized';

    /**
     * @var ImageProvider
     */
    private $imageProvider;

    /**
     * @var string
     */
    private $baseMediaPath;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Task
     */
    private $migrationTask;

    /**
     * @var callable
     */
    private $exceptionCallback;

    /**
     * @var int
     */
    private $countMigrated = 0;

    /**
     * @var int
     */
    private $countFailed = 0;

    /**
     * BatchUploader constructor.
     * @param ImageProvider $imageProvider
     * @param Task $migrationTask
     * @param Logger $logger
     * @param string $baseMediaPath
     * @param callable $exceptionCallback
     */
    public function __construct(
        ImageProvider $imageProvider,
        Task $migrationTask,
        Logger $logger,
        $baseMediaPath,
        callable $exceptionCallback
    ) {
        $this->imageProvider = $imageProvider;
        $this->migrationTask = $migrationTask;
        $this->logger = $logger;
        $this->baseMediaPath = $baseMediaPath;
        $this->exceptionCallback = $exceptionCallback;
    }

    /**
     * @param [Image]
     */
    public function uploadImages(array $images)
    {
        $this->countMigrated = 0;
        foreach ($images as $image) {

            if ($this->migrationTask->hasBeenStopped()) {
                break;
            }
            $this->uploadImage($image);
        }
        $this->logger->notice(sprintf(self::MESSAGE_STATUS, $this->countMigrated, $this->countFailed));
    }

    /**
     * @param Synchronizable $image
     * @return string
     */
    private function getAbsolutePath(Synchronizable $image)
    {
        return sprintf('%s%s', $this->baseMediaPath, $image->getFilename());
    }

    /**
     * @param Synchronizable $image
     * @param int $retryAttempt
     */
    private function uploadImage(Synchronizable $image, $retryAttempt = 0)
    {
        $absolutePath = $this->getAbsolutePath($image);
        $relativePath = $image->getRelativePath();
        $pathDescription = sprintf('%s - %s', $absolutePath, $relativePath);
        $apiImage = Image::fromPath($absolutePath, $relativePath);
        try {
            $this->imageProvider->upload($apiImage);
            $image->tagAsSynchronized();
            $this->countMigrated++;
            $this->logger->notice(sprintf(self::MESSAGE_UPLOADED, $pathDescription));
        } catch (FileExists $e) {
            $image->tagAsSynchronized();
            $this->countMigrated++;
            $this->logger->notice(sprintf(self::MESSAGE_UPLOADED_EXISTS, $image->getFilename()));
        } catch (\Exception $e) {
            if ($retryAttempt < self::MAXIMUM_RETRY_ATTEMPTS) {
                $retryAttempt++;
                $retryMessage = sprintf(' - attempting retry %d', $retryAttempt);
                $this->notify($this->addRetryMessage($retryMessage, $e));
                $this->logger->error($this->buildUploadErrorMessage($e, $pathDescription . $retryMessage));
                usleep(rand(10, 1000) * 1000);
                $this->uploadImage($image, $retryAttempt);
                return;
            }

            $retryMessage = sprintf('- failed after %d retry attempts', $retryAttempt);
            $this->notify($this->addRetryMessage($retryMessage, $e));
            $this->countFailed++;
            $this->logger->error($this->buildUploadErrorMessage($e, $pathDescription . $retryMessage));
        }
    }

    /**
     * @param \Exception $e
     */
    private function notify(\Exception $e)
    {
        $callback = $this->exceptionCallback;
        $callback($e);
    }

    /**
     * @param $retryMessage
     * @param \Exception $e
     * @return \Exception
     */
    private function addRetryMessage($retryMessage, \Exception $e)
    {
        if ($e instanceof MigrationError) {
            $e->suffixMessage($retryMessage);
        } else {
            $e = new \Exception($e->getMessage() . $retryMessage);
        }
        return $e;
    }

    /**
     * @param \Exception $e
     * @param $message
     * @return string
     */
    private function buildUploadErrorMessage(\Exception $e, $message)
    {
        return sprintf(
            self::MESSAGE_UPLOAD_ERROR,
            $e->getMessage(),
            $message
        );
    }
}
