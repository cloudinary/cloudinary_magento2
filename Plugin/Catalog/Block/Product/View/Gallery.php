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

    /**
     * @var \Magento\Catalog\Block\Product\View\Gallery
     */
    protected $productGalleryBlock;

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
            $this->productGalleryBlock = $productGalleryBlock;
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
            $galleryAssets = (array) @json_decode($this->productGalleryBlock->getGalleryImagesJson(), true);
            if (count($galleryAssets)>1) {
                usort($galleryAssets, function ($a, $b) {
                    return $a['position'] - $b['position'];
                });
                /*usort($galleryAssets, function ($a, $b) {
                    return $b['isMain'] - $a['isMain'];
                });*/
            }
            $this->cloudinaryPGoptions['mediaAssets'] = [];
            foreach ($galleryAssets as $key => $value) {
                $publicId = null;
                switch ($value['type']) {
                    case 'image':
                        $publicId = $value['full'] ?: $value['img'];
                        break;
                    case 'video':
                        if (strpos($value['videoUrl'], '.cloudinary.com/') !== false) {
                            $publicId = @pathinfo($value['videoUrl'], PATHINFO_FILENAME) ?: null;
                        }
                        break;
                }
                if ($publicId) {
                    $this->cloudinaryPGoptions['mediaAssets'][] = (object)[
                        "publicId" => $publicId,
                        "mediaType" => $value['type'],
                    ];
                }
            }
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
