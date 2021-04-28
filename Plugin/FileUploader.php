<?php

namespace Cloudinary\Cloudinary\Plugin;

use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Uploader;

class FileUploader
{
    const ALLOWED_EXTENSIONS = ['png', 'gif', 'jpg', 'jpeg'];

    /**
     * @var CloudinaryImageManager
     */
    private $cloudinaryImageManager;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @method __construct
     * @param  CloudinaryImageManager $cloudinaryImageManager
     * @param  DirectoryList          $directoryList
     * @param  ConfigurationInterface $configuration
     */
    public function __construct(
        CloudinaryImageManager $cloudinaryImageManager,
        DirectoryList $directoryList,
        ConfigurationInterface $configuration
    ) {
        $this->cloudinaryImageManager = $cloudinaryImageManager;
        $this->directoryList = $directoryList;
        $this->configuration = $configuration;
    }

    /**
     * @param  Uploader $uploader
     * @param  array    $result
     * @return array
     */
    public function afterSave(Uploader $uploader, $result)
    {
        if (!$this->configuration->isEnabled() || !$this->configuration->hasEnvironmentVariable()) {
            return $result;
        }

        $filepath = $this->absoluteFilePath($result);

        if ($this->isAllowedImageExtension($filepath) && $this->isMediaFilePath($filepath) && !$this->isMediaTmpFilePath($filepath)) {
            $this->cloudinaryImageManager->uploadAndSynchronise(
                Image::fromPath($filepath, $this->mediaRelativePath($filepath))
            );
        }

        return $result;
    }

    /**
     * @param  string $filepath
     * @return string
     */
    protected function isAllowedImageExtension($filepath)
    {
        return in_array(pathinfo($filepath, PATHINFO_EXTENSION), self::ALLOWED_EXTENSIONS);
    }

    /**
     * @param  string $filepath
     * @return bool
     */
    protected function isMediaFilePath($filepath)
    {
        return strpos($filepath, $this->directoryList->getPath('media')) === 0;
    }

    /**
     * @param  string $filepath
     * @return string
     */
    protected function isMediaTmpFilePath($filepath)
    {
        return strpos($filepath, sprintf('%s/tmp', $this->directoryList->getPath('media'))) === 0;
    }

    /**
     * @param  array $result
     * @return string
     */
    protected function absoluteFilePath(array $result)
    {
        return sprintf('%s%s%s', $result['path'], DIRECTORY_SEPARATOR, $result['file']);
    }

    /**
     * @param  string $filepath
     * @return string
     */
    protected function mediaRelativePath($filepath)
    {
        $pubPath = $this->directoryList->getPath(DirectoryList::PUB) . DIRECTORY_SEPARATOR;
        return (strpos($filepath, $pubPath) === 0) ? str_replace($pubPath, '', $filepath) : $filepath;
    }
}
