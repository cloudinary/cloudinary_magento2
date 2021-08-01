<?php

namespace Cloudinary\Cloudinary\Block\Adminhtml\System\Config;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class AutoUploadMapping extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Cloudinary_Cloudinary::config/auto-upload-mapping-btn.phtml';

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
        return $this->_toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('cloudinary/ajax_system_config/autoUploadMapping');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->configuration->isEnabled();
    }

    /**
    * Generate collect button html
    *
    * @return string
    */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
               'id' => 'auto-upload-mapping-btn',
               'label' => __('Map media directory'),
               'disabled' => !$this->configuration->isEnabled(),
           ]
       );

        return $button->toHtml();
    }
}
