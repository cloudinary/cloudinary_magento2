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
        $videoPlayerEnabled = (bool) $allSettings['enabled'] ?? false;
        $videoFreeParams = $allSettings['video_free_params'] ?? null;
        $videoFreeParamsArray = json_decode($videoFreeParams, true);
        if (empty($videoFreeParamsArray)) {
            $videoFreeParams = false;
        }
        $videoFreeParamsArray['player']['cloudName'] = $this->configuration->getCloud();
        $settings['settings'] = $videoFreeParamsArray['player'];
        $settings['player_type'] = 'cloudinary';
        // additional params
        $source = $videoFreeParamsArray['source'] ?? null;
        $settings['source'] = $source ?? [];



        if ($this->configuration->isEnabled() && $videoPlayerEnabled && !$videoFreeParams) {

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
                    // TODO: need to be changed to multiselect as described at https://studio.cloudinary.com/?code=configjson
                    if ($streamModeFormat){
                        $transformation[] =  $streamModeFormat;
                    }
                    if ($streamModeQuality){
                        $transformation[]=  $streamModeQuality;
                    }
                }
                if ($streamMode == 'abr') {
                    $sourceType = $allSettings['source_types'] ?? null;
                }

            $settings = [
                'player_type' => ($this->configuration->isEnabledProductGallery()) ? 'default' : 'cloudinary',
                'settings' => $playerSettings,

            ];

            if ($sourceType) {
                $settings['source'] = ['sourceTypes' => [$sourceType]];
            }
        }


        return $settings;
    }
}
