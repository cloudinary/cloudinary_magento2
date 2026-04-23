<?php

namespace Cloudinary\Cloudinary\Plugin\CustomMediaPath;

use Cloudinary\Cloudinary\Model\Configuration;
use Magento\Framework\App\Config\ScopeConfigInterface;

class MediaGalleryAllowedFolders
{
    /**
     * Magento config path that lists the media-gallery image folders the
     * WYSIWYG image browser is allowed to navigate into.
     */
    private const MEDIA_GALLERY_FOLDERS_PATH
        = 'system/media_storage_configuration/allowed_resources/media_gallery_image_folders';

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Append the admin-configured custom media paths to the media-gallery
     * allow-list so those folders become browsable in the Magento media
     * library (WYSIWYG image manager).
     *
     * @param ScopeConfigInterface $subject
     * @param mixed $result
     * @param string|null $path
     * @return mixed
     */
    public function afterGetValue(
        ScopeConfigInterface $subject,
        $result,
        $path = null
    ) {
        if ($path !== self::MEDIA_GALLERY_FOLDERS_PATH) {
            return $result;
        }

        if (!$this->configuration->isEnabled()
            || !$this->configuration->isEnabledCustomMediaPath()
        ) {
            return $result;
        }

        $customPaths = $this->configuration->getCustomMediaPaths();
        if (empty($customPaths)) {
            return $result;
        }

        $folders = is_array($result) ? $result : [];
        foreach ($customPaths as $index => $customPath) {
            $folders['cloudinary_custom_path_' . $index] = $customPath;
        }

        return $folders;
    }
}
