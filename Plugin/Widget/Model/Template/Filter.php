<?php

namespace Cloudinary\Cloudinary\Plugin\Widget\Model\Template;

use Cloudinary\Cloudinary\Core\Image\ImageFactory;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Cloudinary\Model\Template\Filter as CloudinaryWidgetFilter;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Plugin for Template Filter Model
 */
class Filter
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var ImageFactory
     */
    protected $_imageFactory;

    /**
     * @var UrlGenerator
     */
    protected $_urlGenerator;

    /**
     * @var CloudinaryWidgetFilter
     */
    protected $_cloudinaryWidgetFilter;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ImageFactory $imageFactory
     * @param UrlGenerator $urlGenerator
     * @param CloudinaryWidgetFilter $cloudinaryWidgetFilter
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ImageFactory $imageFactory,
        UrlGenerator $urlGenerator,
        CloudinaryWidgetFilter $cloudinaryWidgetFilter
    ) {
        $this->_storeManager = $storeManager;
        $this->_imageFactory = $imageFactory;
        $this->_urlGenerator = $urlGenerator;
        $this->_cloudinaryWidgetFilter = $cloudinaryWidgetFilter;
    }

    /**
     * Around retrieve media file URL directive
     *
     * @param \Magento\Widget\Model\Template\Filter $widgetFilter
     * @param callable $proceed
     * @param string[] $construction
     * @return string
     */
    public function aroundMediaDirective(\Magento\Widget\Model\Template\Filter $widgetFilter, callable $proceed, $construction)
    {
        $params = $this->_cloudinaryWidgetFilter->getParams($construction[2]);
        if (!isset($params['url'])) {
            return $proceed($construction);
        }

        $storeManager = $this->_storeManager;

        $image = $this->_imageFactory->build(
            $params['url'],
            function () use ($storeManager, $params) {
                return sprintf(
                    '%s%s',
                    $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA),
                    $params['url']
                );
            }
        );

        return $this->_urlGenerator->generateFor($image);
    }
}
