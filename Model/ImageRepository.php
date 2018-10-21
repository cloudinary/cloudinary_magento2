<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Cloudinary\Core\Image\SynchronizationCheck;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;

/**
 * Class ImageRepository
 * @package Cloudinary\Cloudinary\Model
 */
class ImageRepository
{
    private $allowedImgExtensions = ['JPG', 'PNG', 'GIF', 'BMP', 'TIFF', 'EPS', 'PSD', 'SVG', 'WebP'];

    /**
     * @var ReadInterface
     */
    private $mediaDirectory;

    /**
     * @var SynchronizationCheck
     */
    private $synchronizationChecker;

    /**
     * @param Filesystem  $filesystem
     */
    public function __construct(Filesystem $filesystem, SynchronizationCheck $synchronizationChecker)
    {
        $this->mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->synchronizationChecker = $synchronizationChecker;
    }

    /**
     * @return array
     */
    public function findUnsynchronisedImages()
    {
        $images = [];

        foreach ($this->getRecursiveIterator($this->mediaDirectory->getAbsolutePath()) as $item) {
            $absolutePath = $item->getRealPath();
            $relativePath = $this->mediaDirectory->getRelativePath($item->getRealPath());
            if ($this->isValidImageFile($item) && !$this->synchronizationChecker->isSynchronized($relativePath)) {
                $images[] = Image::fromPath($absolutePath, $relativePath);
            }
        }

        return $images;
    }

    /**
     * @param $directory
     * @return \RecursiveIteratorIterator
     */
    private function getRecursiveIterator($directory)
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * @param $item
     * @return bool
     */
    private function isValidImageFile($item)
    {
        return $item->isFile() &&
            strpos($item->getRealPath(), 'cache') === false &&
            strpos($item->getRealPath(), 'tmp') === false &&
            preg_match(
                sprintf('#^[a-z0-9\.\-\_]+\.(?:%s)$#i', implode('|', $this->allowedImgExtensions)),
                $item->getFilename()
            );
    }
}
