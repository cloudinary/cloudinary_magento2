<?php

namespace Cloudinary\Cloudinary\Core;

use Cloudinary\Cloudinary\Core\ImageInterface;
use Cloudinary\Cloudinary\Core\Image\LocalImage;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Core\Image\Transformation\Dimensions;

class UrlGenerator
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var ImageProvider
     */
    private $imageProvider;

    /**
     * @param ConfigurationInterface $configuration
     * @param ImageProvider $imageProvider
     */
    public function __construct(ConfigurationInterface $configuration, ImageProvider $imageProvider)
    {
        $this->configuration = $configuration;
        $this->imageProvider = $imageProvider;
    }

    /**
     * @param ImageInterface $image
     * @param Transformation $transformation
     *
     * @return string
     */
    public function generateFor(ImageInterface $image, Transformation $transformation = null)
    {
        if ($image instanceof LocalImage) {
            return (string)$image;
        }

        return (string)$this->imageProvider->retrieveTransformed(
            $image,
            $transformation ?: $this->configuration->getDefaultTransformation()
        );
    }

    /**
     * @param Image $image
     * @param Dimensions $dimensions
     *
     * @return string
     */
    public function generateWithDimensions(ImageInterface $image, Dimensions $dimensions)
    {
        $transformation = $this->configuration->getDefaultTransformation();

        return $this->generateFor($image, $transformation->withDimensions($dimensions));
    }
}
