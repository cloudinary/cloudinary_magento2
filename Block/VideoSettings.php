<?php
namespace Cloudinary\Cloudinary\Block;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Framework\View\Element\Template;

class VideoSettings extends template
{
    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * @param ConfigurationInterface $configuration
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        ConfigurationInterface $configuration,
        Template\Context $context, array $data = []
    )
    {
        $this->configuration = $configuration;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getVideoSettings() {
        $settings = [
            'player_type' => 'default'
        ];
        $allSettings = $this->configuration->getAllVideoSettings();
        $videoFreeParamsArray = [];
        $sourceTypes = null;
        $videoPlayerEnabled = isset($allSettings['enabled']) && (bool)$allSettings['enabled'];
        $videoFreeParams = isset($allSettings['video_free_params']) ? $allSettings['video_free_params'] : null;
        if (!empty($videoFreeParams) && $videoFreeParams != "{}") {
            $videoFreeParamsArray = json_decode($videoFreeParams, true);
            $videoFreeParamsArray['player']['cloudName'] = $this->configuration->getCloud();
            $settings['settings'] = $videoFreeParamsArray['player'];
            $source = isset($videoFreeParamsArray['source']) ? $videoFreeParamsArray['source'] : null;
            $settings['source'] = $source ?? [];
        }

        if (empty($videoFreeParamsArray)) {
            $videoFreeParams = false;
        }

        // additional params
        if ($this->configuration->isEnabled() && $videoPlayerEnabled) {
            $settings['player_type'] = 'cloudinary';
            if (!$videoFreeParams) {

                $transformation = [];

                $autoplay = isset($allSettings['autoplay']) ? $allSettings['autoplay'] : 'never';
                $controls = isset($allSettings['controls']) ? $allSettings['controls'] : null;
                $isLoop =  isset ($allSettings['loop']) ? (bool) $allSettings['loop'] : false;
                $playerSettings = [
                    "cloudName" => $this->configuration->getCloud(),
                    'controls' => ($controls == 'all'),
                    'autoplay' => ($autoplay != 'never'),
                    'loop' => $isLoop,
                    'chapters' => false
                ];

                $playerSettings['muted'] = false;

                if ($autoplay) {
                    $playerSettings['autoplayMode'] = $autoplay;
                    if ($autoplay != 'never') {
                        $playerSettings['muted'] = true;
                    }
                }

                $streamMode = isset($allSettings['stream_mode']) ? $allSettings['stream_mode'] : null;

                if ($streamMode == 'optimization') {
                    $streamModeFormat = isset($allSettings['stream_mode_format']) ? $allSettings['stream_mode_format'] :  null;
                    $streamModeQuality = isset($allSettings['stream_mode_quality']) ? $allSettings['stream_mode_quality'] : null;
                    $progressiveSourceTypes = isset($allSettings['progressive_sourcetypes']) ? $allSettings['progressive_sourcetypes'] : null;

                    if ($streamModeFormat == 'none' && $progressiveSourceTypes){
                        $sourceTypes = explode(',',$progressiveSourceTypes);
                    }
                    if ($streamModeQuality){
                        $transformation[]=  $streamModeQuality;
                    }
                }
                if ($streamMode == 'abr') {
                    $sourceTypes = isset($allSettings['source_types']) ? [$allSettings['source_types']] : null;
                }

                $settings = [
                    'player_type' => ($this->configuration->isEnabledProductGallery()) ? 'default' : 'cloudinary',
                    'settings' => $playerSettings
                ];

                if ($transformation && is_array($transformation)) {
                    $settings['transformation'] = implode(',',$transformation);
                }

                if ($sourceTypes) {
                    $settings['source'] = ['sourceTypes' => $sourceTypes];
                }
            }
        }

        return $settings;
    }
}
