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

    protected $_casting = [
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


            if ($this->configuration->isEnabledCldVideo()){

                $transformation = [];
                $videoSettings = $this->configuration->getAllVideoSettings();
                $videoFreeParams = $videoSettings['video_free_params'] ?? null;
                if ($videoFreeParams) {
                    $config = json_decode($videoFreeParams, true);
                    $config = array_shift($config);
                    unset($config['cloudName']);
                }


                $config['playerType'] = 'cloudinary';


                if (!$videoFreeParams) {
                    $config = [
                        'playerType' => 'cloudinary',
                        'controls' => $videoSettings['controls'],
                        'chapters' => false,
                        'muted' => false

                    ];
                    $autoplayMode = $videoSettings['autoplay'] ?? null;
                    $config['autoplayMode'] = $videoSettings['autoplay'];
                    if ($autoplayMode && $autoplayMode != 'never') {
                        $config['autoplay'] = true;

                        $config['muted'] = true;

                    } else {
                        $config['autoplay'] = false;
                    }

                    $streamMode = $videoSettings['stream_mode'] ?? null;

                    if ($streamMode == 'optimization') {
                        $streamModeFormat = $videoSettings['stream_mode_format'] ?? null;
                        $streamModeQuality = $videoSettings['stream_mode_quality'] ?? null;
                        if ($streamModeFormat) {
                            $transformation[] = $streamModeFormat;
                        }

                        if ($streamModeQuality) {
                            $transformation[] = $streamModeQuality;
                        }
                    }
                    if ($streamMode == 'abr') {
                        if (isset($videoSettings['source_types'])) {
                            $config['sourceTypes'] = ['auto'];
                            $transformation[] = 'f_' . $videoSettings['source_types'];
                        }
                    }

                    if ($transformation && is_array($transformation)) {
                        $config['transformation'] = implode(',', $transformation);
                    }
                }
                $this->cloudinaryPGoptions['videoProps'] = $config;
            }

            foreach ($this->cloudinaryPGoptions as $key => $value) {
                //Change casting
                if (isset($this->_casting[$key])) {
                    \settype($value, $this->_casting[$key]);
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
            $this->cloudinaryPGoptions['cname'] = $this->getCname();
            $this->cloudinaryPGoptions['queryParam'] = 'AB';
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


    public function getCname()
    {
        $config = $this->configuration->getCredentials();
        return ($config['cname']) ?? '';
    }




    /**
     * @return bool
     */
    public function canDisplayProductGallery()
    {
        return ($this->configuration->isEnabled() && $this->configuration->isEnabledProductGallery()) ? true : false;
    }
}
