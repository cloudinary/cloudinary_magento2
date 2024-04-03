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
        if ($this->configuration->isEnabled()) {
            $allSettings = $this->configuration->getAllVideoSettings();
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
                    if ($streamModeFormat){
                        $transformation[] =  $streamModeFormat;
                    }
                    if ($streamModeQuality){
                        $transformation[]=  $streamModeQuality;
                    }
                }
                if ($streamMode == 'abr') {
                    $sourceType = $allSettings['source_types'] ?? null;
                    if ($sourceType){

                        // TODO: Find out why passing source types is not supported:
                        // TODO: errors: (1) invalid source configuration, (2) No supported media sources,
                        $playerSettings['sourceTypes'] = ['auto'];
                        // $playerSettings['streaming_profile'] = 'auto';
                        $transformation[] = 'f_' . $sourceType;
                    }
                }

            $settings = [
                'player_type' => ($this->configuration->isEnabledProductGallery()) ? 'default' : 'cloudinary',
                'settings' => $playerSettings
            ];

            if ($transformation && is_array($transformation)) {
                $settings['transformation'] = implode(',',$transformation);
            }
        }


        return $settings;
    }
}
