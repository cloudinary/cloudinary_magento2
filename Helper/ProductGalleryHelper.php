<?php

namespace Cloudinary\Cloudinary\Helper;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Model\Configuration;
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

    const CASTING = [
        'themeProps_primary' => 'string',
        'themeProps_onPrimary' => 'string',
        'themeProps_active' => 'string',
        'themeProps_onActive' => 'string',
        'transition' => 'string',
        'aspectRatio' => 'string',
        'navigation' => 'string',
        'zoom' => 'bool',
        'zoomProps_type' => 'string',
        'zoomPropsViewerPosition' => 'string',
        'zoomProps_trigger' => 'string',
        'carouselLocation' => 'string',
        'carouselOffset' => 'float',
        'carouselStyle' => 'string',
        'thumbnailProps_width' => 'float',
        'thumbnailProps_height' => 'float',
        'thumbnailProps_navigationShape' => 'string',
        'thumbnailProps_selectedStyle' => 'string',
        'thumbnailProps_selectedBorderPosition' => 'string',
        'thumbnailProps_selectedBorderWidth' => 'float',
        'thumbnailProps_mediaSymbolShape' => 'string',
        'indicatorProps_shape' => 'string',
    ];

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
     * @param bool $ignoreDisabled Get te options even if the module or the product gallery are disabled
     * @return array
     */
    public function getCloudinaryPGOptions($refresh = false, $ignoreDisabled = false)
    {
        if ((is_null($this->cloudinaryPGoptions) || $refresh) && ($ignoreDisabled || ($this->configuration->isEnabled() && $this->configuration->isEnabledProductGallery()))) {
            $this->cloudinaryPGoptions = $this->configuration->getProductGalleryAll();
            foreach ($this->cloudinaryPGoptions as $key => $value) {
                //Change casting
                if (isset(self::CASTING[$key])) {
                    \settype($value, self::CASTING[$key]);
                    $this->cloudinaryPGoptions[$key] = $value;
                }
                //Build options hierarchy
                $path = explode("_", $key);
                $_path = $path[0];
                if (in_array($_path, ['themeProps','zoomProps','thumbnailProps','indicatorProps'])) {
                    if (!isset($this->cloudinaryPGoptions[$_path])) {
                        $this->cloudinaryPGoptions[$_path] = [];
                    }
                    array_shift($path);
                    $path = implode("_", $path);
                    $this->cloudinaryPGoptions[$_path][$path] = $value;
                    unset($this->cloudinaryPGoptions[$key]);
                }
            }
            if (isset($this->cloudinaryPGoptions['enabled'])) {
                unset($this->cloudinaryPGoptions['enabled']);
            }
            if (isset($this->cloudinaryPGoptions['custom_free_params'])) {
                $customFreeParams = (array) json_decode($this->cloudinaryPGoptions['custom_free_params'], true);
                $this->cloudinaryPGoptions = array_replace_recursive($this->cloudinaryPGoptions, $customFreeParams);
                unset($this->cloudinaryPGoptions['custom_free_params']);
            }
            $this->cloudinaryPGoptions['cloudName'] = $this->getCloudName();
        }

        return $this->cloudinaryPGoptions;
    }

    /**
     * @method getCloudName
     * @return string
     */
    public function getCloudName()
    {
        return (string)$this->configuration->getCloud();
    }

    /**
     * @return bool
     */
    public function canDisplayProductGallery()
    {
        return ($this->configuration->isEnabled() && $this->configuration->isEnabledProductGallery()) ? true : false;
    }
}
