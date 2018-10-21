<?php
namespace Cloudinary\Cloudinary\Model\ProductImageFinder;

class DeletedImageFilter implements ImageFilter
{
    /**
     * @param $imageData
     *
     * @return bool
     */
    public function __invoke($imageData)
    {
        return isset($imageData['removed']) && $imageData['removed'] == 1;
    }
}