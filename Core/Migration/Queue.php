<?php

namespace Cloudinary\Cloudinary\Core\Migration;

class Queue
{
    const MESSAGE_PROCESSING = 'Cloudinary migration: processing';

    const MESSAGE_COMPLETE = 'Cloudinary migration: complete';

    private $migrationTask;

    private $synchronizedMediaRepository;

    private $logger;

    private $batchUploader;

    public function __construct(
        Task $migrationTask,
        SynchronizedMediaRepository $synchronizedMediaRepository,
        BatchUploader $batchUploader,
        Logger $logger
    ) {
        $this->migrationTask = $migrationTask;
        $this->synchronizedMediaRepository = $synchronizedMediaRepository;
        $this->logger = $logger;
        $this->batchUploader = $batchUploader;
    }

    public function process()
    {
        if ($this->migrationTask->hasBeenStopped()) {
            return;
        }

        $images = $this->synchronizedMediaRepository->findUnsynchronisedImages();

        if (!$images) {
            $this->logger->notice(self::MESSAGE_COMPLETE);
            $this->migrationTask->stop();
        } else {
            $this->logger->notice(self::MESSAGE_PROCESSING);
            $this->batchUploader->uploadImages($images);
        }
    }
}
