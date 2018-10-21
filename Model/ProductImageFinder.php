<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Model\ProductImageFinder\ImageCreator;
use Cloudinary\Cloudinary\Model\ProductImageFinder\ImageFilter;
use Cloudinary\Cloudinary\Model\ProductImageFinder\NewImageFilter;
use Cloudinary\Cloudinary\Model\ProductImageFinder\DeletedImageFilter;
use Magento\Catalog\Model\Product;

/**
 * Class ProductImageFinder
 * @package Cloudinary\Cloudinary\Model
 */
class ProductImageFinder
{
    /**
     * @var ImageCreator
     */
    private $imageCreator;

    /**
     * @param ImageCreator $imageCreator
     */
    public function __construct(ImageCreator $imageCreator)
    {
        $this->imageCreator = $imageCreator;
    }

    /**
     * @param Product $product
     *
     * @return \Cloudinary\Cloudinary\Core\Image[]
     */
    public function findNewImages(Product $product)
    {
        return $this->find($product, new NewImageFilter());
    }

    /**
     * @param Product $product
     *
     * @return \Cloudinary\Cloudinary\Core\Image[]
     */
    public function findDeletedImages(Product $product)
    {
        return $this->find($product, new DeletedImageFilter());
    }

    /**
     * @param Product $product
     * @param ImageFilter $filter
     *
     * @return \Cloudinary\Cloudinary\Core\Image[]
     */
    private function find(Product $product, ImageFilter $filter)
    {
        return array_map($this->imageCreator, array_filter(
            $product->getMediaGallery('images') ?: [],
            $filter
        ));
    }
}
