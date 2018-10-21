<?php

namespace Cloudinary\Cloudinary\Core\Image;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image\SynchronizationCheck;
use Cloudinary\Cloudinary\Core\Image;

class ImageFactory
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var SynchronizationCheck
     */
    private $synchronizationChecker;

    /**
     * ImageFactory constructor.
     * @param ConfigurationInterface $configuration
     * @param SynchronizationCheck $synchronizationChecker
     */
    public function __construct(ConfigurationInterface $configuration, SynchronizationCheck $synchronizationChecker)
    {
        $this->configuration = $configuration;
        $this->synchronizationChecker = $synchronizationChecker;
    }

    /**
     * @param $imagePath
     * @return Image
     */
    public function build($imagePath, callable $localPathGenerator)
    {
        $migratedPath = $this->configuration->getMigratedPath($imagePath);

        if ($this->configuration->isEnabled() && $this->synchronizationChecker->isSynchronized($migratedPath)) {
            return Image::fromPath($imagePath, $migratedPath);
        } else {
            return new LocalImage($localPathGenerator);
        }
    }
}
