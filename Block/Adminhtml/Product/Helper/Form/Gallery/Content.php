<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog product form gallery content
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 *
 * @method \Magento\Framework\Data\Form\Element\AbstractElement getElement()
 */
namespace Cloudinary\Cloudinary\Block\Adminhtml\Product\Helper\Form\Gallery;

use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\Json\EncoderInterface;

/**
 * Block for gallery content.
 */
class Content extends \Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery\Content
{
    /**
     * @var string
     */
    protected $_template = 'Cloudinary_Cloudinary::catalog/product/helper/gallery.phtml';

    /**
     * MediaLibraryHelper
     * @var array|null
     */
    protected $mediaLibraryHelper;

    /**
     * @param Context $context
     * @param EncoderInterface $jsonEncoder
     * @param Config $mediaConfig
     * @param MediaLibraryHelper $mediaLibraryHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        Config $mediaConfig,
        MediaLibraryHelper $mediaLibraryHelper,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $mediaConfig, $data);
        $this->mediaLibraryHelper = $mediaLibraryHelper;
    }

    /**
     * Get Cloudinary media library widget options
     *
     * @param string|null $resourceType Resource Types: "image"/"video" or null for "all".
     * @param bool $refresh Refresh options
     * @return string
     */
    public function getCloudinaryMediaLibraryWidgetOptions($resourceType = null, $refresh = false)
    {
        if (!($cloudinaryMLoptions = $this->mediaLibraryHelper->getCloudinaryMLOptions($resourceType, $refresh))) {
            return null;
        }
        return $this->_jsonEncoder->encode(
            [
            'htmlId' => $this->getHtmlId(),
            'cldMLid' => 'product_gallery_' . $this->getHtmlId(),
            'imageUploaderUrl' => $this->_urlBuilder->addSessionParam()->getUrl('cloudinary/ajax/retrieveImage'),
            'triggerSelector' => '#media_gallery_content',
            'triggerEvent' => 'addItem',
            'cloudinaryMLoptions' => $cloudinaryMLoptions,
            ]
        );
    }
}
