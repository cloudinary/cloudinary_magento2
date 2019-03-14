<?php

namespace Cloudinary\Cloudinary\Plugin\Catalog\Block\Product\View;

use Cloudinary\Cloudinary\Helper\ProductGalleryHelper;
use Magento\Framework\Json\EncoderInterface;

class Gallery
{
    /**
     * @var ProductGalleryHelper
     */
    protected $productGalleryHelper;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    protected $processed;
    protected $htmlId;

    /**
     * Cloudinary PG Options
     * @var array|null
     */
    protected $cloudinaryPGoptions;

    /**
     * @param ProductGalleryHelper $productGalleryHelper
     * @param EncoderInterface $jsonEncoder
     */
    public function __construct(
        ProductGalleryHelper $productGalleryHelper,
        EncoderInterface $jsonEncoder
    ) {
        $this->productGalleryHelper = $productGalleryHelper;
        $this->jsonEncoder = $jsonEncoder;
    }

    /**
     * Override product gallery with the one from Cloudinary
     *
     * @param  \Magento\Catalog\Block\Product\View\Gallery $productGalleryBlock
     * @return string
     */
    public function beforeToHtml(\Magento\Catalog\Block\Product\View\Gallery $productGalleryBlock)
    {
        if (!$this->processed && $this->productGalleryHelper->canDisplayProductGallery()) {
            $this->processed = true;
            $productGalleryBlock->setTemplate('Cloudinary_Cloudinary::product/gallery.phtml');
            $productGalleryBlock->setCloudinaryPGOptions($this->getCloudinaryPGOptions());
            $productGalleryBlock->setCldPGid($this->getCldPGid());
        }
    }

    public function getHtmlId()
    {
        if (!$this->htmlId) {
            $this->htmlId = md5(uniqid('', true));
        }
        return $this->htmlId;
    }

    public function getCldPGid()
    {
        return 'cldPGid_' . $this->getHtmlId();
    }

    /**
     * @method getCloudinaryPGOptions
     * @param bool $refresh Refresh options
     * @param bool $ignoreDisabled Get te options even if the module or the product gallery are disabled
     * @return array
     */
    protected function getCloudinaryPGOptions($refresh = false, $ignoreDisabled = false)
    {
        if (is_null($this->cloudinaryPGoptions) || $refresh) {
            $this->cloudinaryPGoptions = $this->productGalleryHelper->getCloudinaryPGOptions($refresh, $ignoreDisabled);
            $this->cloudinaryPGoptions['container'] = '#' . $this->getCldPGid();
            $this->cloudinaryPGoptions['mediaAssets'] = [
                (object)["publicId" => "sample", "mediaType" => "image"],
                (object)["publicId" => "sample", "mediaType" => "image"],
                (object)["publicId" => "sample", "mediaType" => "image"],
            ];
        }
        return $this->jsonEncoder->encode(
            [
            'htmlId' => $this->getHtmlId(),
            'cldPGid' => $this->getCldPGid(),
            'cloudinaryPGoptions' => $this->cloudinaryPGoptions,
            ]
        );
    }
}
