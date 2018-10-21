<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Core\Image\SynchronizationCheck;
use Cloudinary\Cloudinary\Api\SynchronisationRepositoryInterface;
use Cloudinary\Cloudinary\Core\AutoUploadMapping\AutoUploadConfigurationInterface;

class SynchronisationChecker implements SynchronizationCheck
{
    /**
     * @var SynchronisationRepositoryInterface
     */
    private $synchronisationRepository;

    /**
     * @var Configuration
     */
    private $autoUploadConfiguration;

    /**
     * @param SynchronisationRepositoryInterface $synchronisationRepository
     * @param AutoUploadConfigurationInterface $autoUploadConfiguration
     */
    public function __construct(
        SynchronisationRepositoryInterface $synchronisationRepository,
        AutoUploadConfigurationInterface $autoUploadConfiguration
    ) {
        $this->synchronisationRepository = $synchronisationRepository;
        $this->autoUploadConfiguration = $autoUploadConfiguration;
    }

    /**
     * @param $imageName
     * @return bool
     */
    public function isSynchronized($imageName)
    {
        if (!$imageName) {
            return false;
        }

        if ($this->autoUploadConfiguration->isActive()) {
            return true;
        }
        
        return $this->synchronisationRepository->getListByImagePath($imageName)->getTotalCount() > 0;
    }
}
