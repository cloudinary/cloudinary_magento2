<?php

namespace Cloudinary\Cloudinary\Plugin;

use Cloudinary\Cloudinary\Helper\Product\Free as Helper;
use Magento\Catalog\Model\Product;

class ValidateProductTransform
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param Helper $helper
     */
    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param Product $product
     * @param mixed $result
     * @return mixed
     */
    public function afterBeforeSave(Product $product, $result)
    {
        $mediaGalleryImages = $this->helper->getMediaGalleryImages($product);

        $changedTransforms = $this->helper->filterUpdated(
            $product->getCloudinaryFreeTransform(),
            $product->getCloudinaryFreeTransformChanges()
        );

        foreach ($changedTransforms as $id => $transform) {
            $this->helper->validate($this->helper->getImageNameForId($id, $mediaGalleryImages), $transform);
        }

        return $result;
    }
}
