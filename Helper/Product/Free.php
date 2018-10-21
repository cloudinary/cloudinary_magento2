<?php

namespace Cloudinary\Cloudinary\Helper\Product;

use Cloudinary\Cloudinary\Core\Image\Transformation\Freeform;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Catalog\Model\Product;
use Cloudinary\Cloudinary\Model\Config\Backend\Free as FreeModel;

class Free
{
    /**
     * @var FreeModel
     */
    private $freeModel;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @param FreeModel $freeModel
     * @param ConfigurationInterface $configuration
     */
    public function __construct(FreeModel $freeModel, ConfigurationInterface $configuration)
    {
        $this->freeModel = $freeModel;
        $this->configuration = $configuration;
    }

    /**
     * @param string $imageName
     * @param string $transform
     */
    public function validate($imageName, $transform)
    {
        $transformation = $this->configuration
            ->getDefaultTransformation()
            ->withFreeform(Freeform::fromString($transform));

        $this->freeModel->validate($this->freeModel->namedImageUrl($imageName, $transformation));
    }

    /**
     * @param string $id
     * @param array $images
     * @return string
     */
    public function getImageNameForId($id, array $images)
    {
        return array_key_exists($id, $images) ? $images[$id]['file'] : '';
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getMediaGalleryImages(Product $product)
    {
        $mediaGallery = $product->getMediaGallery();

        if (!$mediaGallery || !array_key_exists('images', $mediaGallery)) {
            return [];
        }

        return $mediaGallery['images'];
    }

    /**
     * @param array|null $data
     * @param array|null $isUpdated
     * @return array
     */
    public function filterUpdated($data, $isUpdated)
    {
        if (!is_array($data) || !is_array($isUpdated)) {
            return [];
        }

        return array_filter(
            $data,
            function($id) use ($isUpdated) {
                return $isUpdated[$id] === '1';
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
