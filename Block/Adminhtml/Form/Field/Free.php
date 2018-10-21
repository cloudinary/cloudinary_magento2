<?php

namespace Cloudinary\Cloudinary\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Model\Config\Backend\Free as FreeBackendModel;

class Free extends Field
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var FreeBackendModel
     */
    private $model;

    /**
     * @param Context $context
     * @param ConfigurationInterface $configuration
     * @param FreeBackendModel $model
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigurationInterface $configuration,
        FreeBackendModel $model,
        array $data = []
    ) {
        $this->configuration = $configuration;
        $this->model = $model;

        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->setTemplate('Cloudinary_Cloudinary::config/free.phtml');
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return sprintf(
            '%s%s',
            $element->getElementHtml(),
            $this->model->hasAccountConfigured() ? $this->toHtml() : ''
        );
    }
}
