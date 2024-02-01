<?php
namespace Cloudinary\Cloudinary\Block;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Framework\View\Element\Template;

class VideoSettings extends template
{

    protected $configuration;

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
        $allSettings = $this->configuration->getAllVideoSettings();
        $autoplay = ($allSettings['autoplay'] != "never");
        $controls = ($allSettings == "all");
        $playerSettings = [
            "cloudName" => $this->configuration->getCloud(),
            'controls' => $controls,
            'autoplay' => $autoplay,
            'loop' => (bool) $allSettings['loop'],
            'muted' => (bool) $allSettings['sound']
        ];
        if ($allSettings['use_abr'] != "none") {
            $playerSettings['sourceTypes'] = [$allSettings['use_abr']];
        }
        $settings = [
            'player_type' => $allSettings['player_type'],
            'settings' => $playerSettings
        ];

        return $settings;
    }
}
