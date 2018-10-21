<?php

namespace Cloudinary\Cloudinary\Plugin;

use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Magento\Cms\Model\Wysiwyg\Images\Storage;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Read;

class FileRemover
{
    /**
     * @var CloudinaryImageManager
     */
    private $cloudinaryImageManager;

    /**
     * @var Read
     */
    private $mediaDirectory;

    /**
     * @param CloudinaryImageManager $cloudinaryImageManager
     * @param Filesystem $filesystem
     */
    public function __construct(
        CloudinaryImageManager $cloudinaryImageManager,
        Filesystem $filesystem
    ) {
        $this->cloudinaryImageManager = $cloudinaryImageManager;
        $this->mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
    }

    /**
     * Delete file (and its thumbnail if exists) from storage
     *
     * @param string $target File path to be deleted
     * @return $this
     */
    public function beforeDeleteFile(Storage $storage, $target)
    {
        $this->cloudinaryImageManager->removeAndUnSynchronise(
            Image::fromPath($target, $this->mediaDirectory->getRelativePath($target))
        );

        return [$target];
    }
}
