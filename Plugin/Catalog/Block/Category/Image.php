<?php

namespace Cloudinary\Cloudinary\Plugin\Catalog\Block\Category;

use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Configuration\Configuration;
use Magento\Catalog\Model\Category;
use Cloudinary\Cloudinary\Core\Image as CoreImage;
use Cloudinary\Cloudinary\Model\ResourceModel\MediaLibraryMap\CollectionFactory as MediaLibraryMapCollectionFactory;
use Magento\Catalog\ViewModel\Category\Image as CategoryImageViewModel;

class Image
{
    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * @var ConfigurationBuilder
     */
    protected $configurationBuilder;

    /**
     * @var Transformation
     */
    protected $transformation;

    /**
     * @var MediaLibraryMapCollectionFactory
     */
    protected $mediaLibraryMapCollectionFactory;

    /**
     * @var bool
     */
    private $authorised = false;

    /**
     * @param ConfigurationInterface $configuration
     * @param ConfigurationBuilder $configurationBuilder
     * @param Transformation $transformation
     * @param MediaLibraryMapCollectionFactory $mediaLibraryMapCollectionFactory
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        Transformation $transformation,
        MediaLibraryMapCollectionFactory $mediaLibraryMapCollectionFactory
    ) {
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
        $this->transformation = $transformation;
        $this->mediaLibraryMapCollectionFactory = $mediaLibraryMapCollectionFactory;
    }

    /**
     * Authorize Cloudinary configuration
     *
     * @return void
     */
    protected function authorise()
    {
        if (!$this->authorised && $this->configuration->isEnabled()) {
            Configuration::instance($this->configurationBuilder->build());
            $this->authorised = true;
        }
    }

    /**
     * Plugin after getUrl to return Cloudinary version
     *
     * For rendition images (.renditions/*) without local mapping, returns the local image URL.
     * For regular uploaded images, returns the Cloudinary URL.
     *
     * @param CategoryImageViewModel $subject
     * @param string $result
     * @param Category $category
     * @param string $attributeCode
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetUrl(
        CategoryImageViewModel $subject,
        string $result,
        Category $category,
        string $attributeCode = 'image'
    ): string {
        $this->authorise();

        $imagePath = $category->getData($attributeCode);
        if (!$this->configuration->isEnabled() || !$imagePath) {
            return $result;
        }

        try {
            $imageId = ltrim($imagePath, '/');
            $isRenditionImage = strpos($imageId, '.renditions/') !== false;

            // Check if this is a rendition image (gallery selection or other rendition)
            // These images are stored locally and should not use Cloudinary unless there's a mapping
            if ($isRenditionImage && !$this->configuration->isEnabledLocalMapping()) {
                // Local mapping not enabled - return local image URL for rendition images
                return $result;
            }

            // Try to find mapping if local mapping is enabled
            if ($this->configuration->isEnabledLocalMapping()) {
                $mappedPublicId = $this->getMappedPublicId($imageId);
                if ($mappedPublicId) {
                    // If it's a full URL, return it directly
                    if (preg_match('/https?:\/\//i', $mappedPublicId)) {
                        return $mappedPublicId;
                    }
                    $imageId = $mappedPublicId;
                } elseif ($isRenditionImage) {
                    // Rendition image without mapping - return local image URL
                    return $result;
                }
            }

            // Generate Cloudinary URL using the imageId
            $imagePath = Media::fromParams($imageId, [
                'transformation' => $this->transformation->build(),
                'secure' => true,
                'sign_url' => $this->configuration->getUseSignedUrls(),
                'version' => 1
            ]) . '?_i=AB';

            $image = CoreImage::fromPath($imagePath, '');
            return (string) $image;

        } catch (\Exception $e) {
            return $result; // fallback to original if Cloudinary fails
        }
    }

    /**
     * Get mapped public ID from database
     *
     * @param string $imageId
     * @return string|null
     */
    private function getMappedPublicId($imageId)
    {
        preg_match('/(cld_[A-Za-z0-9]{13}_).+$/i', $imageId, $cldUniqid);
        if (!$cldUniqid || !isset($cldUniqid[1])) {
            return null;
        }

        $mapped = $this->mediaLibraryMapCollectionFactory->create()
            ->addFieldToFilter('cld_uniqid', $cldUniqid[1])
            ->setPageSize(1)
            ->getFirstItem();

        if ($mapped && ($origPublicId = $mapped->getCldPublicId())) {
            return $origPublicId;
        }

        return null;
    }
}
