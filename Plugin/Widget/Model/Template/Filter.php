<?php

namespace Cloudinary\Cloudinary\Plugin\Widget\Model\Template;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image\ImageFactory;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Cloudinary\Model\Template\Filter as CloudinaryWidgetFilter;
use Magento\Framework\Registry;
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
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @method __construct
     * @param  StoreManagerInterface  $storeManager
     * @param  ImageFactory           $imageFactory
     * @param  UrlGenerator           $urlGenerator
     * @param  ConfigurationInterface $configuration
     * @param  CloudinaryWidgetFilter $cloudinaryWidgetFilter
     * @param  Registry               $coreRegistry
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ImageFactory $imageFactory,
        UrlGenerator $urlGenerator,
        ConfigurationInterface $configuration,
        CloudinaryWidgetFilter $cloudinaryWidgetFilter,
        Registry $coreRegistry
    ) {
        $this->_imageFactory = $imageFactory;
        $this->_urlGenerator = $urlGenerator;
        $this->_configuration = $configuration;
        $this->_cloudinaryWidgetFilter = $cloudinaryWidgetFilter;
        $this->_coreRegistry = $coreRegistry;
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

        $generated = $this->_urlGenerator->generateFor($image);

        if ($this->_configuration->isEnabledLazyload() && $this->_configuration->isLazyloadAutoReplaceCmsBlocks()) {
            $this->_coreRegistry->register('cloudinary_generated_' . md5($generated), $image, true);
        }

        return $generated;
    }
}
