<?php

namespace Cloudinary\Cloudinary\Plugin\Catalog\Block\Category;

use Magento\Catalog\ViewModel\Category\Image as CategoryImageViewModel;
use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Configuration\Configuration;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Model\Category;
use Cloudinary\Cloudinary\Core\Image as CoreImage;
use Cloudinary\Cloudinary\Model\ResourceModel\MediaLibraryMap\CollectionFactory as MediaLibraryMapCollectionFactory;

class Image
{
    protected $configuration;
    protected $configurationBuilder;
    protected $transformation;
    protected $mediaLibraryMapCollectionFactory;

    private $authorised = false;

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

    protected function authorise()
    {
        if (!$this->authorised && $this->configuration->isEnabled()) {
            Configuration::instance($this->configurationBuilder->build());
            $this->authorised = true;
        }
    }

    /**
     * Plugin after getUrl to return Cloudinary version
     */
    public function afterGetUrl(
        \Magento\Catalog\ViewModel\Category\Image $subject,
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
            $filename = preg_replace('/\.[^.]+$/', '', ltrim($imagePath, '/'));
            $publicId = null;

            // Try to find mapping by searching cld_public_id with the filename pattern

            $collection = $this->mediaLibraryMapCollectionFactory->create();
            $collection->addFieldToFilter('cld_public_id', ['like' => '%' . $filename]);

            if ($collection->getSize() > 0) {
                $mapping = $collection->getFirstItem();
                // Use exact public_id from mapping (already without extension)
                $publicId = $mapping->getData('cld_public_id');
            }

            // Fallback: construct public_id from imagePath (without extension)
           if (!$publicId) {
               if (preg_match('/cld_[a-zA-Z0-9]+_/', $filename)) {
                   $filename = preg_replace('/cld_[a-zA-Z0-9]+_/', '', $filename);
               }
               $publicId = $filename;
           }

            $asset =  Media::fromParams($publicId, [
                'transformation' => $this->transformation->build(),
                'secure' => true,
                'sign_url' => $this->configuration->getUseSignedUrls(),
                'version' => 1
            ]) . '?_i=AB';

            $image = CoreImage::fromPath($asset, '');
            return (string) $image;

        } catch (\Exception $e) {
            return $result; // fallback to original if Cloudinary fails
        }
    }
}
