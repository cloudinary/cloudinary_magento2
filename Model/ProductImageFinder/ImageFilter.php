<?php

namespace Cloudinary\Cloudinary\Model\ProductImageFinder;

/**
 * Interface ImageFilter
 * @package Cloudinary\Cloudinary\Model\ProductImageFinder
 */
interface ImageFilter
{
    /**
     * @param $imageData
     * @return boolean
     */
    public function __invoke($imageData);
}