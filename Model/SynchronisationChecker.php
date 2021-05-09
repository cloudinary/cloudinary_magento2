<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Api\SynchronisationRepositoryInterface;
use Cloudinary\Cloudinary\Core\AutoUploadMapping\AutoUploadConfigurationInterface;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image\SynchronizationCheck;

class SynchronisationChecker implements SynchronizationCheck
{
    /**
     * @var SynchronisationRepositoryInterface
     */
    private $synchronisationRepository;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var Configuration
     */
    private $autoUploadConfiguration;

    /**
     * @var MediaLibraryMapFactory
     */
    private $mediaLibraryMapFactory;

    /**
     * @method __construct
     * @param  SynchronisationRepositoryInterface $synchronisationRepository
     * @param  ConfigurationInterface             $configuration
     * @param  AutoUploadConfigurationInterface   $autoUploadConfiguration
     * @param  MediaLibraryMapFactory             $mediaLibraryMapFactory
     */
    public function __construct(
        SynchronisationRepositoryInterface $synchronisationRepository,
        ConfigurationInterface $configuration,
        AutoUploadConfigurationInterface $autoUploadConfiguration,
        MediaLibraryMapFactory $mediaLibraryMapFactory
    ) {
        $this->synchronisationRepository = $synchronisationRepository;
        $this->configuration = $configuration;
        $this->autoUploadConfiguration = $autoUploadConfiguration;
        $this->mediaLibraryMapFactory = $mediaLibraryMapFactory;
    }

    /**
     * @param  $imageName
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

        if ($this->configuration->isEnabledLocalMapping()) {
            //Look for a match on the mapping table:
            preg_match('/(cld_[A-Za-z0-9]{13}_).+$/i', $imageName, $cldUniqid);
            if ($cldUniqid && isset($cldUniqid[1])) {
                $mapped = $this->mediaLibraryMapFactory->create()->getCollection()->addFieldToFilter("cld_uniqid", $cldUniqid[1])->setPageSize(1)->getFirstItem();
                if ($mapped && ($origPublicId = $mapped->getCldPublicId())) {
                    return true;
                }
            }
        }

        return $this->synchronisationRepository->isSynchronizedImagePath($imageName);
    }
}
