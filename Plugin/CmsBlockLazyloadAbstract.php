<?php

namespace Cloudinary\Cloudinary\Plugin;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Cloudinary\Model\Transformation as TransformationModel;
use Cloudinary\Cloudinary\Model\TransformationFactory;
use Magento\Framework\Registry;

/**
 * Class CmsBlockLazyloadAbstract
 */
class CmsBlockLazyloadAbstract
{
    /**
     * @var ConfigurationInterface
     */
    protected $_configuration;

    /**
     * @var UrlGenerator
     */
    protected $_urlGenerator;

    /**
     * @var TransformationModel
     */
    protected $_transformationModel;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @method __construct
     * @param  ConfigurationInterface $configuration
     * @param  UrlGenerator           $urlGenerator
     * @param  TransformationFactory  $transformationFactory
     * @param  Registry               $coreRegistry
     */
    public function __construct(
        ConfigurationInterface $configuration,
        UrlGenerator $urlGenerator,
        TransformationFactory $transformationFactory,
        Registry $coreRegistry
    ) {
        $this->_configuration = $configuration;
        $this->_urlGenerator = $urlGenerator;
        $this->_transformationModel = $transformationFactory->create();
        $this->_coreRegistry = $coreRegistry;
    }

    protected function process($subject, $html)
    {
        if (!$this->_configuration->isEnabled() || !$this->_configuration->isEnabledLazyload() || !$this->_configuration->isLazyloadAutoReplaceCmsBlocks() || in_array($subject->getBlockId(), $this->_configuration->getLazyloadIgnoredCmsBlocksArray())) {
            return $html;
        }

        if (stripos($html, "<img ") !== false) {
            $dom = new \domDocument();
            $useErrors = libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            libxml_use_internal_errors($useErrors);
            $dom->preserveWhiteSpace = false;
            $modified = 0;

            foreach ($dom->getElementsByTagName('img') as $element) {
                if (strpos($element->getAttribute('class'), "lazyload") === false && strpos($element->getAttribute('class'), "owl-lazy") === false && ($image = $this->_coreRegistry->registry('cloudinary_generated_' . hash('sha256', $element->getAttribute('src')))) !== null) {
                    if (!($placeholderUrl = $this->_urlGenerator->generateFor($image, $this->_configuration->getDefaultTransformation()->withFreeform($this->_configuration->getLazyloadPlaceholderFreeform())))) {
                        continue;
                    }
                    $this->_coreRegistry->unregister('cloudinary_generated_' . hash('sha256', $element->getAttribute('src')));
                    $modified++;
                    $element->setAttribute('class', 'cloudinary-lazyload ' . $element->getAttribute('class'));
                    $element->setAttribute('data-original', $element->getAttribute('src'));
                    $element->setAttribute('src', $placeholderUrl);
                }
            }

            if ($modified) {
                $html = $dom->saveHTML();
            }
        }

        return $html;
    }
}
