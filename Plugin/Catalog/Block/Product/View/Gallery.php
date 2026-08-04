<?php

namespace Cloudinary\Cloudinary\Plugin\Catalog\Block\Product\View;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Helper\ProductGalleryHelper;
use Cloudinary\Cloudinary\Model\ProductSpinsetMapFactory;
use Magento\Framework\DataObject;
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
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var ProductSpinsetMapFactory
     */
    protected $productSpinsetMapFactory;

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
     * True when a gallery asset could not be resolved to a Cloudinary asset
     * (e.g. an image served locally because image sync is disabled)
     * @var bool
     */
    protected $hasUnresolvedAssets = false;

    /**
     * @method __construct
     * @param  ProductGalleryHelper   $productGalleryHelper
     * @param  EncoderInterface       $jsonEncoder
     * @param  ConfigurationInterface $configuration
     */
    public function __construct(
        ProductGalleryHelper $productGalleryHelper,
        EncoderInterface $jsonEncoder,
        ConfigurationInterface $configuration,
        ProductSpinsetMapFactory $productSpinsetMapFactory
    ) {
        $this->productGalleryHelper = $productGalleryHelper;
        $this->jsonEncoder = $jsonEncoder;
        $this->configuration = $configuration;
        $this->productSpinsetMapFactory = $productSpinsetMapFactory;
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
            $cloudinaryPGOptions = $this->getCloudinaryPGOptions();
            if ($this->hasUnresolvedAssets) {
                // The Cloudinary gallery widget can only render assets that exist on
                // Cloudinary. When any gallery item is served from another source
                // (e.g. a local image while image sync is disabled), keep Magento's
                // default gallery so every item is displayed.
                return;
            }
            $productGalleryBlock->setTemplate('Cloudinary_Cloudinary::product/gallery.phtml');
            $productGalleryBlock->setCloudinaryPGOptions($cloudinaryPGOptions);
            $productGalleryBlock->setCldPGid($this->getCldPGid());
        }
    }
    public function getCname()
    {
        $config = $this->configuration->getCredentials();
        return ($config['cname']) ?? '';
    }

    /**
     * A URL is a Cloudinary URL only when it targets this account's cloud (or its
     * configured CNAME). An empty CNAME must not match — strpos($url, '') is 0, which
     * would classify every URL (including local media URLs) as Cloudinary.
     *
     * @param string|null $url
     * @return bool
     */
    private function isCloudinaryUrl($url)
    {
        if (!$url) {
            return false;
        }
        $cloudName = $this->productGalleryHelper->getCloudName();
        if (strpos($url, '.cloudinary.com/') !== false
            && (strpos($url, '/' . $cloudName . '/') !== false || strpos($url, '://' . $cloudName) !== false)) {
            return true;
        }
        $cname = $this->getCname();
        return $cname && strpos($url, $cname) !== false;
    }
    public function getHtmlId()
    {
        if (!$this->htmlId) {
            $this->htmlId = hash('sha256', uniqid('', true));
        }
        return $this->htmlId;
    }

    public function getCldPGid()
    {
        return 'cldPGid_' . $this->getHtmlId();
    }

    /**
     * Retrieve product images in JSON format
     *
     * @return string
     */
    protected function getGalleryImagesJson()
    {
        $imagesItems = [];
        /** @var DataObject $image */
        foreach ($this->productGalleryBlock->getGalleryImages() as $image) {
            $imageItem = new DataObject(
                [
                    'file' => $image->getData('file'),
                    'thumb' => $image->getData('small_image_url'),
                    'img' => $image->getData('medium_image_url'),
                    'full' => $image->getData('large_image_url'),
                    'caption' => ($image->getLabel() ?: $this->productGalleryBlock->getProduct()->getName()),
                    'position' => $image->getData('position'),
                    'isMain'   => $this->productGalleryBlock->isMainImage($image),
                    'type' => str_replace('external-', '', $image->getMediaType()),
                    'videoUrl' => $image->getVideoUrl(),
                ]
            );
            foreach ($this->productGalleryBlock->getGalleryImagesConfig()->getItems() as $imageConfig) {
                $imageItem->setData(
                    $imageConfig->getData('json_object_key'),
                    $image->getData($imageConfig->getData('data_object_key'))
                );
            }
            $imagesItems[] = $imageItem->toArray();
        }
        if (empty($imagesItems)) {
            return $this->productGalleryBlock->getGalleryImagesJson();
        }
        return $this->jsonEncoder->encode($imagesItems);
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
            $galleryAssets = (array) json_decode($this->getGalleryImagesJson(), true);
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
                $publicId = $url = $transformation = null;
                if ($value['type'] === 'image' && isset($value['file']) && $value['file'] ) {
                    //Check if image is a spinset:
                    $cldspinset = $this->productSpinsetMapFactory->create()->getCollection()->addFieldToFilter("image_name", $value['file'])->setPageSize(1)->getFirstItem();
                    if ($cldspinset && ($cldspinset = $cldspinset->getCldspinset())) {
                        $this->cloudinaryPGoptions['mediaAssets'][] = (object)[
                            "tag" => $cldspinset,
                            "mediaType" => 'spin'
                        ];
                        continue;
                    }
                    //==================================//
                    $url = $value['full'] ?: $value['img'];
                } elseif ($value['type'] === 'video') {
                    $url = $value['videoUrl'];
                }
                if ($this->isCloudinaryUrl($url)) {
                    $parsed = $this->configuration->parseCloudinaryUrl($url);
                    $publicId = ($value['type'] === 'image') ? $parsed['publicId'] . '.' . $parsed['extension'] : $parsed['publicId'];
                    $transformation = \str_replace('/', ',', $parsed['transformations_string']);
                }
                if ($publicId) {
                    $this->cloudinaryPGoptions['mediaAssets'][] = (object)[
                        "publicId" => $publicId,
                        "mediaType" => $value['type'],
                        "transformation" => $transformation,
                    ];
                } elseif ($url) {
                    $this->hasUnresolvedAssets = true;
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
