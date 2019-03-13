<?php

namespace Cloudinary\Cloudinary\Helper;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Framework\App\Helper\Context;

class ProductGalleryHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * Cloudinary PG Options
     * @var array|null
     */
    protected $cloudinaryPGoptions;

    /**
     * @param Context $context
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        Context $context,
        ConfigurationInterface $configuration
    ) {
        parent::__construct($context);
        $this->configuration = $configuration;
    }

    /**
     * @method getCloudinaryPGOptions
     * @param bool $refresh Refresh options
     * @return array
     */
    public function getCloudinaryPGOptions($refresh = true)
    {
        if ((is_null($this->cloudinaryMLoptions) || $refresh) && $this->configuration->isEnabled() && $this->configuration->isEnabledProductGallery()) {
            $this->cloudinaryPGoptions = $this->configuration->getProductGalleryAll();
            foreach ($this->cloudinaryPGoptions as $key => $value) {
                $path = explode("_", $key);
                if (in_array($path[0], ['themeProps','zoomProps','thumbnailProps','indicatorProps'])) {
                    if (!isset($this->cloudinaryPGoptions[$path[0]])) {
                        $this->cloudinaryPGoptions[$path[0]] = [];
                    }
                    array_shift($path);
                    $path = implode("_", $path);
                    $this->cloudinaryPGoptions[$path[0]][$path] = $value;
                    unset($this->cloudinaryPGoptions[$key]);
                }
            }
            if (isset($this->cloudinaryPGoptions['enabled'])) {
                unset($this->cloudinaryPGoptions['enabled']);
            }
            if (isset($this->cloudinaryPGoptions['custom_free_params'])) {
                $customFreeParams = (array) @json_decode($this->cloudinaryPGoptions['custom_free_params'], true);
                $this->cloudinaryPGoptions = array_merge_recursive($this->cloudinaryPGoptions, $customFreeParams);
                unset($this->cloudinaryPGoptions['custom_free_params']);
            }
        }

        return $this->cloudinaryMLoptions;
    }
}
