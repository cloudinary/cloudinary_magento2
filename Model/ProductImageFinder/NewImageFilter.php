<?php
namespace Cloudinary\Cloudinary\Model\ProductImageFinder;

/**
 * Class NewImageFinder
 * @package Cloudinary\Cloudinary\Model\ProductImageFinder
 */
class NewImageFilter implements ImageFilter
{
    /**
     * @param $imageData
     * @return bool
     */
    public function __invoke($imageData)
    {
        return !empty($imageData['new_file']);
    }
}