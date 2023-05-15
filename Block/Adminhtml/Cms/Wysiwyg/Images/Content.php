<?php

namespace Cloudinary\Cloudinary\Block\Adminhtml\Cms\Wysiwyg\Images;

use _PHPStan_532094bc1\Nette\Neon\Exception;
use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\App\ProductMetadataInterface;

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


    protected $productMetadata;

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
        ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $data);
        $this->mediaLibraryHelper = $mediaLibraryHelper;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Get Cloudinary media library widget options
     *
     * @param bool $multiple Allow multiple
     * @param bool $refresh Refresh options
     * @return string
     */
    public function getCloudinaryMediaLibraryWidgetOptions($multiple = false, $refresh = false)
    {
        if (!($cloudinaryMLoptions = $this->mediaLibraryHelper->getCloudinaryMLOptions($multiple, $refresh))) {
            return null;
        }

        try {
            if (version_compare($this->productMetadata->getVersion(), '2.3.5', '<=')) {
                $imageUploadUrl = $this->_urlBuilder->addSessionParam()->getUrl('cloudinary/cms_wysiwyg_images/upload', ['type' => $this->_getMediaType()]);
            } else {
                $imageUploadUrl = $this->_urlBuilder->getUrl('cloudinary/cms_wysiwyg_images/upload', ['type' => $this->_getMediaType()]);
            }

            //Try to add session param on Magento versions prior to 2.3.5

        } catch (\Exception $e) {
            //Catch deprecation error on Magento 2.3.5 and above
            throw new Exception($e->getMessage());
        }

        return $this->_jsonEncoder->encode(
            [
            'cldMLid' => 'wysiwyg_media_gallery',
            'imageUploaderUrl' => $imageUploadUrl,
            'triggerSelector' => '.media-gallery-modal',
            'triggerEvent' => 'fileuploaddone',
            'cloudinaryMLoptions' => $cloudinaryMLoptions,
            'addTmpExtension' => false,
            'cloudinaryMLshowOptions' => $this->mediaLibraryHelper->getCloudinaryMLshowOptions("image"),
            ]
        );
    }

    /**
     * Return current media type based on request or data
     *
     * @return string
     */
    protected function _getMediaType()
    {
        if ($this->hasData('media_type')) {
            return $this->_getData('media_type');
        }
        return $this->getRequest()->getParam('type');
    }
}
