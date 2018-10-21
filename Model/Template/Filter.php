<?php

namespace Cloudinary\Cloudinary\Model\Template;

use Magento\Widget\Model\Template\Filter as WidgetFilter;
use Cloudinary\Cloudinary\Core\Image\ImageFactory;
use Cloudinary\Cloudinary\Core\UrlGenerator;

class Filter extends WidgetFilter
{
    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Variable\Model\VariableFactory $coreVariableFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\UrlInterface $urlModel
     * @param \Pelago\Emogrifier $emogrifier
     * @param \Magento\Email\Model\Source\Variables $configVariables
     * @param \Magento\Widget\Model\ResourceModel\Widget $widgetResource
     * @param \Magento\Widget\Model\Widget $widget
     * @param ImageFactory $imageFactory
     * @param UrlGenerator $urlGenerator
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Variable\Model\VariableFactory $coreVariableFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\UrlInterface $urlModel,
        \Pelago\Emogrifier $emogrifier,
        \Magento\Email\Model\Source\Variables $configVariables,
        \Magento\Widget\Model\ResourceModel\Widget $widgetResource,
        \Magento\Widget\Model\Widget $widget,
        ImageFactory $imageFactory,
        UrlGenerator $urlGenerator
    ) {
        $this->imageFactory = $imageFactory;
        $this->urlGenerator = $urlGenerator;

        parent::__construct(
            $string,
            $logger,
            $escaper,
            $assetRepo,
            $scopeConfig,
            $coreVariableFactory,
            $storeManager,
            $layout,
            $layoutFactory,
            $appState,
            $urlModel,
            $emogrifier,
            $configVariables,
            $widgetResource,
            $widget
        );
    }

    /**
     * Retrieve media file URL directive
     *
     * @param string[] $construction
     * @return string
     */
    public function mediaDirective($construction)
    {
        $params = $this->getParameters($construction[2]);
        $storeManager = $this->_storeManager;

        $image = $this->imageFactory->build(
            $params['url'],
            function() use ($storeManager, $params) {
                return sprintf(
                    '%s%s',
                    $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA),
                    $params['url']
                );
            }
        );

        return $this->urlGenerator->generateFor($image);
    }
}
