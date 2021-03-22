<?php

namespace Cloudinary\Cloudinary\Plugin;

use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image;
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
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @method __construct
     * @param  CloudinaryImageManager $cloudinaryImageManager
     * @param  Filesystem             $filesystem
     * @param  ConfigurationInterface $configuration
     */
    public function __construct(
        CloudinaryImageManager $cloudinaryImageManager,
        Filesystem $filesystem,
        ConfigurationInterface $configuration
    ) {
        $this->cloudinaryImageManager = $cloudinaryImageManager;
        $this->mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->configuration = $configuration;
    }

    /**
     * Delete file (and its thumbnail if exists) from storage
     *
     * @param  string $target File path to be deleted
     * @return $this
     */
    public function beforeDeleteFile(Storage $storage, $target)
    {
        if (!$this->configuration->isEnabled() || !$this->configuration->hasEnvironmentVariable()) {
            return [$target];
        }

        $this->cloudinaryImageManager->removeAndUnSynchronise(
            Image::fromPath($target, $this->mediaDirectory->getRelativePath($target))
        );

        return [$target];
    }
}
