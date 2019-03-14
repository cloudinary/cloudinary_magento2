<?php

namespace Cloudinary\Cloudinary\Block\Catalog;

use Cloudinary\Cloudinary\Helper\ProductGalleryHelper;

/**
 * @api
 * @since 100.0.2
 */
class CloudinaryProductGallery extends \Magento\Catalog\Block\Product\Gallery
{
    /**
     * @var ProductGalleryHelper
     */
    protected $productGalleryHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ProductGalleryHelper $productGalleryHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        ProductGalleryHelper $productGalleryHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->productGalleryHelper = $productGalleryHelper;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        /*if ($this->productGalleryHelper->canDisplayProductGallery()) {
            $this->setTemplate('Cloudinary_Cloudinary::product/gallery.phtml');
        }*/
        return parent::_toHtml();
    }

    /**
     * @method getCloudinaryPGOptions
     * @param bool $refresh Refresh options
     * @param bool $ignoreDisabled Get te options even if the module or the product gallery are disabled
     * @return array
     */
    public function getCloudinaryPGOptions($refresh = false, $ignoreDisabled = false)
    {
        return $this->productGalleryHelper->getCloudinaryPGOptions($refresh, $ignoreDisabled);
    }
}
