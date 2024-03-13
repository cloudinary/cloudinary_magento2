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
            $autoplay = (isset($allSettings['autoplay']) && $allSettings['autoplay'] != "never");
            $controls = (isset($allSettings['controls']) && $allSettings['controls'] == "all");
            $playerSettings = [
                "cloudName" => $this->configuration->getCloud(),
                'controls' => $controls,
                'autoplay' => $autoplay,
                'loop' => (bool) $allSettings['loop'],
                'chapters' => false
            ];
            if (isset($allSettings['stream_mode']) && $allSettings['stream_mode'] != "none") {
                if ($allSettings['stream_mode'] == 'optimization') {
                    if (isset($allSettings['stream_mode_format']) && $allSettings['stream_mode_format'] != 'none'){
                        $transformation[] =  $allSettings['stream_mode_format'];
                    }
                    if (isset($allSettings['stream_mode_quality']) && $allSettings['stream_mode_quality'] != 'none'){
                        $transformation[]=  $allSettings['stream_mode_quality'];
                    }
                } else if ($allSettings['stream_mode'] == 'abr') {
                    if (isset($allSettings['source_types'])){
                         // $allSettings['source_types']
                        // TODO: Find out why passing source types is not supported:
                        // TODO: errors: (1) invalid source configuration, (2) No supported media sources,
                        $playerSettings['sourceTypes'] = ['auto'];
                        // $playerSettings['streaming_profile'] = 'auto';
                        $transformation[] = 'f_' . $allSettings['source_types'];
                    }
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
