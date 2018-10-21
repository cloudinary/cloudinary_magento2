<?php

namespace Cloudinary\Cloudinary\Plugin;

use Cloudinary\Cloudinary\Core\Image\ImageFactory;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Magento\Catalog\Model\Product\Media\Config as CatalogMediaConfig;

class MediaConfig
{
    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @param ImageFactory $imageFactory
     * @param UrlGenerator $urlGenerator
     */
    public function __construct(ImageFactory $imageFactory, UrlGenerator $urlGenerator)
    {
        $this->imageFactory = $imageFactory;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param  CatalogMediaConfig $mediaConfig
     * @param  \Closure           $originalMethod
     * @param  string             $file
     *
     * @return string
     */
    public function aroundGetMediaUrl(CatalogMediaConfig $mediaConfig, \Closure $originalMethod, $file)
    {
        $image = $this->imageFactory->build(
            $mediaConfig->getBaseMediaPath() . $file,
            function() use ($originalMethod, $file) { return $originalMethod($file); }
        );

        return $this->urlGenerator->generateFor($image); 
    }
}
