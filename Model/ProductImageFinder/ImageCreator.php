<?php

namespace Cloudinary\Cloudinary\Model\ProductImageFinder;

use Cloudinary\Cloudinary\Core\Image;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class ImageCreator
 * @package Cloudinary\Cloudinary\Model\ProductImageFinder
 */
class ImageCreator
{
    /**
     * @var ReadInterface
     */
    private $mediaDirectory;

    /**
     * @var string
     */
    private $baseMediaPath;

    /**
     * ImageCreator constructor.
     *
     * @param Filesystem $filesystem
     * @param MediaConfig $mediaConfig
     */
    public function __construct(Filesystem $filesystem, MediaConfig $mediaConfig)
    {
        $this->mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->baseMediaPath = $mediaConfig->getBaseMediaPath();
    }

    /**
     * @param array $imageData
     *
     * @return Image
     */
    public function __invoke(array $imageData)
    {
        $fullPath = $this->baseMediaPath . $imageData['file'];
        $relativePath = 'media' . DIRECTORY_SEPARATOR . $fullPath;

        return Image::fromPath(
            $this->mediaDirectory->getAbsolutePath($fullPath),
            $relativePath
        );
    }
}

