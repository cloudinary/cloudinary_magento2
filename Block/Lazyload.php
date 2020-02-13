<?php
namespace Cloudinary\Cloudinary\Block;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\Template\Context;

class Lazyload extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @method __construct
     * @param  Context                $context
     * @param  ConfigurationInterface $configuration
     * @param  EncoderInterface       $jsonEncoder
     * @param  array                  $data
     */
    public function __construct(
        Context $context,
        ConfigurationInterface $configuration,
        EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        $this->configuration = $configuration;
        $this->jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data);
    }

    /**
     * @method isEnabledLazyload
     * @return boolean
     */
    public function isEnabledLazyload()
    {
        return $this->configuration->isEnabled() && $this->configuration->isEnabledLazyload();
    }

    /**
     * @method getLazyloadOptions
     * @param  boolean            $json
     * @return string|array
     */
    public function getLazyloadOptions($json = true)
    {
        $options = [
            'threshold' => $this->configuration->getLazyloadThreshold(),
            'effect' => $this->configuration->getLazyloadEffect(),
            'placeholder' => $this->configuration->getLazyloadPlaceholder(),
        ];
        return $json ? $this->jsonEncoder->encode($options) : $options;
    }
}
