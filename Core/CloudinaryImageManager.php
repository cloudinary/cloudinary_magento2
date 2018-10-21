<?php

namespace Cloudinary\Cloudinary\Core;

use Cloudinary\Cloudinary\Core\Exception\FileExists;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CloudinaryImageManager
 * @package Cloudinary\Cloudinary\Core
 */
class CloudinaryImageManager
{
    const MESSAGE_UPLOADING_IMAGE = 'Uploading image: %s';
    const MESSAGE_UPLOADED_EXISTS = 'Image exists - marked as synchronised: %s';
    const MESSAGE_RETRY = 'Failed with error: %s - attempting retry %d';
    const MAXIMUM_RETRY_ATTEMPTS = 3;

    /**
     * @var ImageProvider
     */
    private $cloudinaryImageProvider;

    /**
     * @var SynchroniseAssetsRepositoryInterface
     */
    private $synchronisationRepository;

    /**
     * CloudinaryImageManager constructor.
     *
     * @param ImageProvider $cloudinaryImageProvider
     * @param SynchroniseAssetsRepositoryInterface $synchronisationRepository
     */
    public function __construct(
        ImageProvider $cloudinaryImageProvider,
        SynchroniseAssetsRepositoryInterface $synchronisationRepository
    ) {
        $this->cloudinaryImageProvider = $cloudinaryImageProvider;
        $this->synchronisationRepository = $synchronisationRepository;
    }

    /**
     * @param Image $image
     * @param OutputInterface|null $output
     * @throws \Exception
     */
    public function uploadAndSynchronise(Image $image, OutputInterface $output = null, $retryAttempt = 0)
    {
        try {
            $this->report($output, sprintf(self::MESSAGE_UPLOADING_IMAGE, $image));
            $this->cloudinaryImageProvider->upload($image);
        } catch (FileExists $e) {
            $this->report($output, sprintf(self::MESSAGE_UPLOADED_EXISTS, $image));
        } catch (\Exception $e) {
            if ($retryAttempt < self::MAXIMUM_RETRY_ATTEMPTS) {
                $retryAttempt++;
                $this->report($output, sprintf(self::MESSAGE_RETRY, $e->getMessage(), $retryAttempt));
                usleep(rand(10, 1000) * 1000);
                $this->uploadAndSynchronise($image, $output, $retryAttempt);
                return;
            }

            throw $e;
        }

        $this->synchronisationRepository->saveAsSynchronized($image->getRelativePath());
    }

    /**
     * @param Image $image
     */
    public function removeAndUnSynchronise(Image $image)
    {
        $this->cloudinaryImageProvider->delete($image);
        $this->synchronisationRepository->removeSynchronised($image->getRelativePath());
    }

    /**
     * @param OutputInterface|null $output
     * @param string $message
     */
    private function report(OutputInterface $output = null, $message = '')
    {
        if ($output) {
            $output->writeln($message);
        }
    }
}
