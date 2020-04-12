<?php

namespace Cloudinary\Cloudinary\Block\Adminhtml\System\Config;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ModuleVersion extends Field
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @method __construct
     * @param  Context                $context
     * @param  ConfigurationInterface $configuration
     * @param  array                  $data
     */
    public function __construct(
        Context $context,
        ConfigurationInterface $configuration,
        array $data = []
    ) {
        $this->configuration = $configuration;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return "<div>{$this->configuration->getModuleVersion()}</div>";
    }
}
