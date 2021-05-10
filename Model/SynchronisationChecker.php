<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Api\SynchronisationRepositoryInterface;
use Cloudinary\Cloudinary\Core\AutoUploadMapping\AutoUploadConfigurationInterface;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image\SynchronizationCheck;
use Magento\Framework\Registry;

class SynchronisationChecker implements SynchronizationCheck
{
    /**
     * @var string
     */
    private $imageNameHash;

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
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @method __construct
     * @param  SynchronisationRepositoryInterface $synchronisationRepository
     * @param  ConfigurationInterface             $configuration
     * @param  AutoUploadConfigurationInterface   $autoUploadConfiguration
     * @param  MediaLibraryMapFactory             $mediaLibraryMapFactory
     * @param  Registry                           $coreRegistry
     */
    public function __construct(
        SynchronisationRepositoryInterface $synchronisationRepository,
        ConfigurationInterface $configuration,
        AutoUploadConfigurationInterface $autoUploadConfiguration,
        MediaLibraryMapFactory $mediaLibraryMapFactory,
        Registry $coreRegistry
    ) {
        $this->synchronisationRepository = $synchronisationRepository;
        $this->configuration = $configuration;
        $this->autoUploadConfiguration = $autoUploadConfiguration;
        $this->mediaLibraryMapFactory = $mediaLibraryMapFactory;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * @method cacheResult
     * @param  bool        $result
     * @return mixed
     */
    private function cacheResult($result)
    {
        $this->coreRegistry->unregister($this->imageNameHash);
        $this->coreRegistry->register($this->imageNameHash, $result);
        return $result;
    }

    /**
     * @method cacheResult
     * @return mixed
     */
    private function getFromCache()
    {
        return $this->coreRegistry->registry($this->imageNameHash);
    }

    /**
     * @param  string $imageName
     * @param  bool   $refresh
     * @return bool
     */
    public function isSynchronized($imageName, $refresh = false)
    {
        if (!$imageName) {
            return false;
        }

        if ($this->autoUploadConfiguration->isActive()) {
            return true;
        }

        $this->imageNameHash = hash('sha256', 'cld_sync_check_' . (string) $imageName);
        if (!$refresh && ($cacheResult = $this->getFromCache()) !== null) {
            return $cacheResult;
        }

        if ($this->configuration->isEnabledLocalMapping()) {
            //Look for a match on the mapping table:
            preg_match('/(cld_[A-Za-z0-9]{13}_).+$/i', $imageName, $cldUniqid);
            if ($cldUniqid && isset($cldUniqid[1])) {
                $mapped = $this->mediaLibraryMapFactory->create()->getCollection()->addFieldToFilter("cld_uniqid", $cldUniqid[1])->setPageSize(1)->getFirstItem();
                if ($mapped && ($origPublicId = $mapped->getCldPublicId())) {
                    return $this->cacheResult(true);
                }
            }
        }

        return $this->cacheResult($this->synchronisationRepository->isSynchronizedImagePath($imageName));
    }
}
