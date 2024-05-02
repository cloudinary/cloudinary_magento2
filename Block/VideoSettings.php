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

        $videoPlayerEnabled = isset($allSettings['enabled']) && (bool)$allSettings['enabled'];
        $videoFreeParams = isset($allSettings['video_free_params']) ? $allSettings['video_free_params'] : null;
        $videoFreeParamsArray = json_decode($videoFreeParams, true);
        $sourceTypes = null;
        if (empty($videoFreeParamsArray)) {
            $videoFreeParams = false;
        }
        $videoFreeParamsArray['player']['cloudName'] = $this->configuration->getCloud();
        $settings['settings'] = $videoFreeParamsArray['player'];

        // additional params
        $source = $videoFreeParamsArray['source'] ?? null;
        $settings['source'] = $source ?? [];

        if ($this->configuration->isEnabled() && $videoPlayerEnabled) {
            $settings['player_type'] = 'cloudinary';
            if (!$videoFreeParams) {

                $transformation = [];

                $autoplay = $allSettings['autoplay'];
                $controls = $allSettings['controls'];
                $isLoop = (bool) $allSettings['loop'];
                $playerSettings = [
                    "cloudName" => $this->configuration->getCloud(),
                    'controls' => ($controls == 'all'),
                    'autoplay' => ($autoplay != 'never'),
                    'loop' => $isLoop,
                    'chapters' => false
                ];

                $playerSettings['muted'] = false;

                $autoplayMode = $allSettings['autoplay'] ?? null;
                if ($autoplayMode) {
                    $playerSettings['autoplayMode'] = $autoplayMode;
                    if ($autoplayMode != 'never') {
                        $playerSettings['muted'] = true;
                    }
                }

                $streamMode = $allSettings['stream_mode'] ?? null;

                if ($streamMode == 'optimization') {
                    $streamModeFormat = $allSettings['stream_mode_format'] ?? null;
                    $streamModeQuality = $allSettings['stream_mode_quality'] ?? null;
                    $progressiveSourceTypes = $allSettings['progressive_sourcetypes'] ?? null;

                    if ($streamModeFormat == 'none' && $progressiveSourceTypes){
                        $sourceTypes = explode(',',$progressiveSourceTypes);
                    }
                    if ($streamModeQuality){
                        $transformation[]=  $streamModeQuality;
                    }
                }
                if ($streamMode == 'abr') {
                    $sourceTypes = $allSettings['source_types'] ? [$allSettings['source_types']] : null;
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
