<?php

namespace Cloudinary\Cloudinary\Plugin\Catalog\Model\Product\Image;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image\ImageFactory as CloudinaryImageFactory;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Core\Image\Transformation\Crop;
use Cloudinary\Cloudinary\Core\Image\Transformation\Dimensions;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Cloudinary\Model\Transformation as TransformationModel;
use Cloudinary\Cloudinary\Model\TransformationFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Magento\Catalog\Model\Product\Image\UrlBuilder as CatalogUrlBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\ConfigInterface;

class UrlBuilder
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
     * Build image url using base path and params
     *
     * @param  CatalogUrlBuilder $catalogUrlBuilder
     * @param  callable          $proceed
     * @param  string            $baseFilePath
     * @param  string            $imageDisplayArea
     * @return string
     */
    public function aroundGetUrl(CatalogUrlBuilder $catalogUrlBuilder, callable $proceed, string $baseFilePath, string $imageDisplayArea)
    {
        $url = $proceed($baseFilePath, $imageDisplayArea);

        if (!$this->configuration->isEnabled()) {
            return $url;
        }

        if (class_exists('\Magento\Catalog\Model\Product\Image\ParamsBuilder')) {
            $this->imageParamsBuilder = $this->objectManager->get('\Magento\Catalog\Model\Product\Image\ParamsBuilder');
        } else {
            //Skip on Magento versions prior to 2.3
            return $url;
        }

        try {
            if (strpos($url, $this->configuration->getMediaBaseUrl() . 'catalog/product') === 0) {
                $imageArguments = $this->presentationConfig->getViewConfig()->getMediaAttributes(
                    'Magento_Catalog',
                    CatalogImageHelper::MEDIA_TYPE_CONFIG_NODE,
                    $imageDisplayArea
                );
                $imageMiscParams = $this->imageParamsBuilder->build($imageArguments);

                $imagePath = preg_replace('/^' . preg_quote($this->configuration->getMediaBaseUrl(), '/') . '/', '/', $url);
                $imagePath = preg_replace('/\/catalog\/product\/cache\/[a-f0-9]{32}\//', '/', $imagePath);

                $image = $this->cloudinaryImageFactory->build(
                    sprintf('catalog/product%s', $imagePath),
                    function () use ($url) {
                        return $url;
                    }
                );

                $generatedImageUrl = $this->urlGenerator->generateFor(
                    $image,
                    $this->transformationModel->addFreeformTransformationForImage(
                        $this->createTransformation($imageMiscParams),
                        $imagePath
                    )
                );

                $url = $generatedImageUrl;
            }
        } catch (\Exception $e) {
            $url = $proceed($baseFilePath, $imageDisplayArea);
        }

        return $url;
    }

    /**
     * @param  array $imageMiscParams
     * @return Transformation
     */
    private function createTransformation(array $imageMiscParams)
    {
        $imageMiscParams['image_height'] = (isset($imageMiscParams['image_height'])) ? $imageMiscParams['image_height'] : null;
        $imageMiscParams['image_width'] = (isset($imageMiscParams['image_width'])) ? $imageMiscParams['image_width'] : null;
        $dimensions = $this->dimensions ?: Dimensions::fromWidthAndHeight($imageMiscParams['image_width'], $imageMiscParams['image_height']);
        $transform = $this->configuration->getDefaultTransformation()->withDimensions($dimensions);

        if (isset($imageMiscParams['keep_frame'])) {
            $this->keepFrame = ($imageMiscParams['keep_frame'] === 'frame') ? true : false;
        }

        if ($this->keepFrame) {
            $transform->withCrop(Crop::fromString('lpad'))
                ->withDimensions(Dimensions::squareMissingDimension($dimensions));
        } else {
            $transform->withCrop(Crop::fromString('fit'));
        }

        return $transform;
    }
}
