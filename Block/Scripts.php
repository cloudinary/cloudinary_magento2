<?php

namespace Cloudinary\Cloudinary\Block;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Scripts extends Template
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var MediaLibraryHelper
     */
    protected $_helper;

    /**
     * @param Context $context
     * @param ConfigurationInterface $configuration
     * @param MediaLibraryHelper $mediaHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigurationInterface $configuration,
        MediaLibraryHelper $mediaHelper,
        array $data = []
    )
    {
        $this->configuration = $configuration;
        $this->_helper = $mediaHelper;
        parent::__construct($context, $data);
    }

    /**
     * @method isEnabledLazyload
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->configuration->isEnabled();
    }

    /**
     * @return string|null
     */
    public function getCname()
    {
        return $this->_helper->getCname();
    }

}
