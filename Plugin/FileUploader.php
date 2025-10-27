<?php

namespace Cloudinary\Cloudinary\Plugin;

use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Cloudinary\Model\MediaLibraryMapFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Uploader;
use Magento\Framework\Filesystem;

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
     * @var MediaLibraryMapFactory
     */
    private $mediaLibraryMapFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @method __construct
     * @param  CloudinaryImageManager $cloudinaryImageManager
     * @param  DirectoryList          $directoryList
     * @param  ConfigurationInterface $configuration
     * @param  MediaLibraryMapFactory $mediaLibraryMapFactory
     * @param  Filesystem             $filesystem
     */
    public function __construct(
        CloudinaryImageManager $cloudinaryImageManager,
        DirectoryList $directoryList,
        ConfigurationInterface $configuration,
        MediaLibraryMapFactory $mediaLibraryMapFactory,
        Filesystem $filesystem
    ) {
        $this->cloudinaryImageManager = $cloudinaryImageManager;
        $this->directoryList = $directoryList;
        $this->configuration = $configuration;
        $this->mediaLibraryMapFactory = $mediaLibraryMapFactory;
        $this->filesystem = $filesystem;
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



        if ($this->isAllowedImageExtension($filepath) && $this->isMediaFilePath($filepath)) {
            try {
                $relativePath = $this->mediaRelativePath($filepath);

                // Normalize path: remove /tmp/ to match final location
                $normalizedPath = preg_replace('#/tmp/#', '/', $relativePath);

                $image = Image::fromPath($filepath, $normalizedPath);
                $uploadResults = $this->cloudinaryImageManager->uploadAndSynchronise($image);

                // fallback to existing files
                if (isset($uploadResults['file_exists']) && $uploadResults['file_exists']) {
                    $normalizedPath = $uploadResults['file_exists'];
                }

                // Only save mapping if upload succeeded and settings enabled
                $cldUniqid = $this->configuration->generateCLDuniqid();
                // Save public_id without extension (e.g., media/catalog/category/_4_2_3)
                $publicId = isset($uploadResults['public_id']) ? $uploadResults['public_id'] : preg_replace('/\.[^.]+$/', '', $normalizedPath);
                $this->mediaLibraryMapFactory->create()
                    ->setCldUniqid($cldUniqid)
                    ->setCldPublicId($publicId)
                    ->setFreeTransformation(null)
                    ->save();

            } catch (\Cloudinary\Cloudinary\Core\Exception\FileExists $e) {
                // Image already exists in Cloudinary - skip mapping creation
            } catch (\Exception $e) {
                // Upload failed - skip mapping creation
            }
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
        // Check for standard tmp path
        $standardTmpCheck = strpos($filepath, sprintf('%s/tmp', $this->directoryList->getPath('media'))) === 0;

        // Also check for catalog/tmp (category images go to catalog/tmp/category)
        $catalogTmpCheck = strpos($filepath, $this->directoryList->getPath('media') . '/catalog/tmp') === 0;

        return $standardTmpCheck || $catalogTmpCheck;
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
