<?php

namespace Cloudinary\Cloudinary\Plugin\Catalog\Block\Product;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image\ImageFactory as CloudinaryImageFactory;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Core\Image\Transformation\Crop;
use Cloudinary\Cloudinary\Core\Image\Transformation\Dimensions;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Cloudinary\Model\Configuration;
use Cloudinary\Cloudinary\Model\Transformation as TransformationModel;
use Cloudinary\Cloudinary\Model\TransformationFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\Image as ImageBlock;
use Magento\Catalog\Block\Product\ImageFactory as CatalogImageFactory;
use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\ConfigInterface;

class ImageFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ConfigInterface
     */
    private $presentationConfig;

    /**
     * @var \Magento\Catalog\Model\Product\Image\ParamsBuilder
     */
    private $imageParamsBuilder;

    /**
     * @var CloudinaryImageFactory
     */
    private $cloudinaryImageFactory;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var ProductInterface
     */
    private $product;

    /**
     * @var Dimensions
     */
    private $dimensions;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var string
     */
    private $imageFile;

    /**
     * @var bool
     */
    private $keepFrame;

    /**
     * @var TransformationModel
     */
    private $transformationModel;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ConfigInterface        $presentationConfig
     * @param CloudinaryImageFactory $cloudinaryImageFactory
     * @param UrlGenerator           $urlGenerator
     * @param ConfigurationInterface $configuration
     * @param TransformationFactory  $transformationFactory
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ConfigInterface $presentationConfig,
        CloudinaryImageFactory $cloudinaryImageFactory,
        UrlGenerator $urlGenerator,
        ConfigurationInterface $configuration,
        TransformationFactory $transformationFactory
    ) {
        $this->objectManager = $objectManager;
        $this->presentationConfig = $presentationConfig;
        $this->cloudinaryImageFactory = $cloudinaryImageFactory;
        $this->urlGenerator = $urlGenerator;
        $this->configuration = $configuration;
        $this->transformationModel = $transformationFactory->create();
        $this->dimensions = null;
        $this->imageFile = null;
        $this->keepFrame = true;
    }

    /**
     * Retrieve image custom attributes for HTML element
     *
     * @param array $attributes
     * @return string
     */
    private function getStringCustomAttributes(array $attributes)
    {
        $result = [];
        foreach ($attributes as $name => $value) {
            if ($name != 'class') {
                $result[] = $name . '="' . $value . '"';
            }
        }
        return !empty($result) ? implode(' ', $result) : '';
    }

    /**
     * Create image block from product
     *
     * @param  CatalogImageFactory $catalogImageFactory
     * @param  callable            $proceed
     * @param  Product             $product
     * @param  string              $imageId
     * @param  array|null          $attributes
     * @return ImageBlock
     */
    public function aroundCreate(CatalogImageFactory $catalogImageFactory, callable $proceed, $product = null, $imageId = null, $attributes = null)
    {
        $imageBlock = call_user_func_array($proceed, array_slice(func_get_args(), 2));

        if (!$this->configuration->isEnabled()) {
            return $imageBlock;
        }

        if ($imageBlock->getImageUrl() === 'no_selection') {
            return $imageBlock;
        }

        if ($this->configuration->isEnabledLazyload()) {
            $useOldImageTheme = is_string($imageBlock->getCustomAttributes()) ? 'old_' : '';
            $imageBlock->setTemplate(
                \preg_match('/\/image_with_borders.phtml$/', $imageBlock->getTemplate()) ?
                    'Cloudinary_Cloudinary::product/' . $useOldImageTheme . 'image_with_borders.phtml' : 'Cloudinary_Cloudinary::' . $useOldImageTheme . 'product/image.phtml'
            );
            $imageBlock->setLazyloadPlaceholder(Configuration::LAZYLOAD_DATA_PLACEHOLDER);
        }

        //Skip on Magento versions prior to 2.3
        if (is_array($product) || !class_exists('\Magento\Catalog\Model\Product\Image\ParamsBuilder')) {
            return $imageBlock;
        }

        $this->imageParamsBuilder = $this->objectManager->get('\Magento\Catalog\Model\Product\Image\ParamsBuilder');

        try {
            if (strpos($imageBlock->getImageUrl(), $this->configuration->getMediaBaseUrl() . 'catalog/product') === 0) {
                $viewImageConfig = $this->presentationConfig->getViewConfig()->getMediaAttributes(
                    'Magento_Catalog',
                    CatalogImageHelper::MEDIA_TYPE_CONFIG_NODE,
                    $imageId
                );
                $imageMiscParams = $this->imageParamsBuilder->build($viewImageConfig);

                $imagePath = preg_replace('/^' . preg_quote($this->configuration->getMediaBaseUrl(), '/') . '/', '/', $imageBlock->getImageUrl());
                $imagePath = preg_replace('/\/catalog\/product\/cache\/[a-f0-9]{32}\//', '/', $imagePath);

                $image = $this->cloudinaryImageFactory->build(
                    sprintf('catalog/product%s', $imagePath),
                    function () use ($imageBlock) {
                        return $imageBlock->getImageUrl();
                    }
                );

                $transformations = $this->createTransformation($imageMiscParams);

                if ($this->configuration->isEnabledProductFreeTransformations()) {
                    $transformations = $this->transformationModel->addFreeformTransformationForImage(
                        $transformations,
                        $imagePath
                    );
                }

                $generatedImageUrl = $this->urlGenerator->generateFor(
                    $image,
                    $transformations
                );

                $imageBlock->setOriginalImageUrl($imageBlock->setImageUrl());
                $imageBlock->setImageUrl($generatedImageUrl);

                if ($this->configuration->isEnabledLazyload()) {
                    $generatedImageUrl = $this->urlGenerator->generateFor(
                        $image,
                        $transformations->withFreeform($this->configuration->getLazyloadPlaceholderFreeform())
                    );
                    $imageBlock->setLazyloadPlaceholder($generatedImageUrl);
                }
            }
        } catch (\Exception $e) {
            $imageBlock = $proceed($product, $imageId, $attributes);
        }

        return $imageBlock;
    }

    /**
     * @param  array $imageMiscParams
     * @return Transformation
     */
    private function createTransformation(array $imageMiscParams)
    {
        $dimensions = $this->getDimensions($imageMiscParams);
        $transform = $this->configuration->getDefaultTransformation()->withDimensions($dimensions);

        if (isset($imageMiscParams['keep_frame'])) {
            $this->keepFrame = ($imageMiscParams['keep_frame'] === 'frame') ? true : false;
        }

        if ($this->keepFrame) {
            $transform->withCrop(Crop::lpad())
                ->withDimensions(Dimensions::squareMissingDimension($dimensions));
        } else {
            $transform->withCrop(Crop::limit());
        }

        return $transform;
    }

    /**
     * @param  array $imageMiscParams
     * @return Dimensions
     */
    private function getDimensions(array $imageMiscParams)
    {
        $imageMiscParams['image_height'] = (isset($imageMiscParams['image_height'])) ? $imageMiscParams['image_height'] : null;
        $imageMiscParams['image_width'] = (isset($imageMiscParams['image_width'])) ? $imageMiscParams['image_width'] : null;
        return $this->dimensions ?: Dimensions::fromWidthAndHeight($imageMiscParams['image_width'], $imageMiscParams['image_height']);
    }
}
