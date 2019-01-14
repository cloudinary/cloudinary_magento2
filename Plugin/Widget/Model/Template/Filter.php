<?php

namespace Cloudinary\Cloudinary\Plugin\Widget\Model\Template;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
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
     * @var ImageFactory
     */
    protected $_imageFactory;

    /**
     * @var UrlGenerator
     */
    protected $_urlGenerator;

    /**
     * @var ConfigurationInterface
     */
    protected $_configuration;

    /**
     * @var CloudinaryWidgetFilter
     */
    protected $_cloudinaryWidgetFilter;

    /**
     * @param ImageFactory           $imageFactory
     * @param UrlGenerator           $urlGenerator
     * @param CloudinaryWidgetFilter $cloudinaryWidgetFilter
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ImageFactory $imageFactory,
        UrlGenerator $urlGenerator,
        ConfigurationInterface $configuration,
        CloudinaryWidgetFilter $cloudinaryWidgetFilter
    ) {
        $this->_imageFactory = $imageFactory;
        $this->_urlGenerator = $urlGenerator;
        $this->_configuration = $configuration;
        $this->_cloudinaryWidgetFilter = $cloudinaryWidgetFilter;
    }

    /**
     * Around retrieve media file URL directive
     *
     * @param  \Magento\Widget\Model\Template\Filter $widgetFilter
     * @param  callable                              $proceed
     * @param  string[]                              $construction
     * @return string
     */
    public function aroundMediaDirective(\Magento\Widget\Model\Template\Filter $widgetFilter, callable $proceed, $construction)
    {
        if (!$this->_configuration->isEnabled()) {
            return $proceed($construction);
        }

        $params = $this->_cloudinaryWidgetFilter->getParams($construction[2]);
        if (!isset($params['url'])) {
            return $proceed($construction);
        }

        $url = (preg_match('/^&quot;.+&quot;$/', $params['url'])) ? preg_replace('/(^&quot;)|(&quot;$)/', '', $params['url']) : $params['url'];

        $image = $this->_imageFactory->build(
            $url,
            function () use ($proceed, $construction) {
                return $proceed($construction);
            }
        );

        return $this->_urlGenerator->generateFor($image);
    }
}
