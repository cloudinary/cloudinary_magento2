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
        $allSettings = $this->configuration->getAllVideoSettings();

        $autoplay = (isset($allSettings['autoplay']) && $allSettings['autoplay'] != "never");
        $controls = (isset($allSettings['controls']) && $allSettings['controls'] == "all");
        $playerSettings = [
            "cloudName" => $this->configuration->getCloud(),
            'controls' => $controls,
            'autoplay' => $autoplay,
            'loop' => (bool) $allSettings['loop'],
            'muted' => (bool) $allSettings['sound']
        ];
        if (isset($allSettings['use_abr']) && $allSettings['use_abr'] != "none") {
            $playerSettings['sourceTypes'] = [$allSettings['use_abr']];
        }
        $settings = [
            'player_type' => $allSettings['player_type'],
            'settings' => $playerSettings
        ];

        return $settings;
    }
}
