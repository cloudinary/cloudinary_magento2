<?php

namespace Cloudinary\Cloudinary\Block\Adminhtml\Cms\Wysiwyg\Images;

use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Json\EncoderInterface;

/**
 * Wysiwyg Images content block
 *
 * @api
 * @since 100.0.2
 */
class Content extends \Magento\Cms\Block\Adminhtml\Wysiwyg\Images\Content
{
    /**
     * MediaLibraryHelper
     * @var array|null
     */
    protected $mediaLibraryHelper;

    /**
     * @param Context $context
     * @param EncoderInterface $jsonEncoder
     * @param MediaLibraryHelper $mediaLibraryHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        MediaLibraryHelper $mediaLibraryHelper,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $data);
        $this->mediaLibraryHelper = $mediaLibraryHelper;
    }

    /**
     * Get Cloudinary media library widget options
     *
     * @return string
     */
    public function getCloudinaryMediaLibraryWidgetOptions($refresh = false)
    {
        if (!($cloudinaryMLoptions = $this->mediaLibraryHelper->getCloudinaryMLOptions($refresh))) {
            return null;
        }
        return $this->_jsonEncoder->encode(
                [
                    'htmlId' => $this->getHtmlId(),
                    'uploaderUrl' => $this->_urlBuilder->addSessionParam()->getUrl('cloudinary/product_gallery/upload'),
                    'cloudinaryMLoptions' => $cloudinaryMLoptions,
                ]
            );
    }
}
