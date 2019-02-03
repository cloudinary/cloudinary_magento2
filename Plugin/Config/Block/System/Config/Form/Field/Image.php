<?php
/**
 * CLOUDINARY-74: Implementation of Cloudinary-ML in System configuration (still in progress)
 * Magento will ignore this file until we'll enable this plugin by uncommenting the relevant line on app/code/Cloudinary/Cloudinary/etc/adminhtml/di.xml
 */

namespace Cloudinary\Cloudinary\Plugin\Config\Block\System\Config\Form\Field;

use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Framework\Escaper;
use Magento\Framework\Json\EncoderInterface;

/**
 * Plugin for UiComponent Media DataType
 */
class Image
{
    /**
     * Escaper
     * @var Escaper
     */
    protected $escaper;

    /**
     * EncoderInterface
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * MediaLibraryHelper
     * @var array|null
     */
    protected $mediaLibraryHelper;

    /**
     * @param Escaper $escaper
     * @param EncoderInterface $jsonEncoder
     * @param MediaLibraryHelper $mediaLibraryHelper
     */
    public function __construct(
        Escaper $escaper,
        EncoderInterface $jsonEncoder,
        MediaLibraryHelper $mediaLibraryHelper
    ) {
        $this->escaper = $escaper;
        $this->jsonEncoder = $jsonEncoder;
        $this->mediaLibraryHelper = $mediaLibraryHelper;
    }

    /**
     * Get the Html for the element.
     *
     * @param \Magento\Config\Block\System\Config\Form\Field\Image $block
     * @param string $html
     * @return string
     */
    public function afterGetElementHtml(\Magento\Config\Block\System\Config\Form\Field\Image $block, $html)
    {
        // TODO: Add JS logics & handlers for after image insert
        if (($cloudinaryMLoptions = $this->mediaLibraryHelper->getCloudinaryMLOptions(false))) {
            $html .= '<button id="media_gallery_add_from_cloudinary_button"
                title="' . $this->escaper->escapeHtml(__('Add from Cloudinary')) . '"
                data-role="add-from-cloudinary-button"
                type="button"
                data-mage-init=\'{"cloudinaryMediaLibraryModal": ' . $this->jsonEncoder->encode(['cloudinaryMLoptions' => $cloudinaryMLoptions, 'cloudinaryMLshowOptions' => $this->mediaLibraryHelper->getCloudinaryMLshowOptions("image")]) . '}\'
                class="add-from-cloudinary-button cloudinary-blue-button-with-logo small-ver sm-top-bottom-margin">
                <span>' . $this->escaper->escapeHtml(__('Add from Cloudinary')) . '</span>
            </button>';
        }
        return $html;
    }
}
