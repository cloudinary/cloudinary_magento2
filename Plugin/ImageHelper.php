<?php

namespace Cloudinary\Cloudinary\Plugin;

use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Core\Image\ImageFactory;
use Cloudinary\Cloudinary\Core\Image\Transformation\Dimensions;
use Cloudinary\Cloudinary\Core\Image\Transformation\Crop;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Cloudinary\Cloudinary\Model\Transformation as TransformationModel;
use Cloudinary\Cloudinary\Model\TransformationFactory;

class ImageHelper
{
    /**
     * @var ImageFactory
     */
    private $imageFactory;

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
     * @param ImageFactory $imageFactory
     * @param UrlGenerator $urlGenerator
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        ImageFactory $imageFactory,
        UrlGenerator $urlGenerator,
        ConfigurationInterface $configuration,
        TransformationFactory $transformationFactory
    ) {
        $this->imageFactory = $imageFactory;
        $this->urlGenerator = $urlGenerator;
        $this->configuration = $configuration;
        $this->transformationModel = $transformationFactory->create();
        $this->dimensions = null;
        $this->imageFile = null;
    }

    /**
     * @param  CatalogImageHelper $helper
     * @param  ProductInterface   $product
     * @param  string             $imageId
     * @param  array              $attributes
     *
     * @return array
     */
    public function beforeInit(CatalogImageHelper $helper, $product, $imageId, $attributes = [])
    {
        $this->product = $product;
        $this->dimensions = null;
        $this->imageFile = null;
        $this->keepFrame = true;
        return [$product, $imageId, $attributes];
    }

    /**
     * @param  CatalogImageHelper $helper
     * @param  string             $file
     *
     * @return string[]
     */
    public function beforeSetImageFile(CatalogImageHelper $helper, $file)
    {
        $this->imageFile = $file;
        return [$file];
    }

    /**
     * @param  CatalogImageHelper $helper
     * @param  int                $width
     * @param  int                $height
     *
     * @return array
     */
    public function beforeResize(CatalogImageHelper $helper, $width, $height = null)
    {
        $this->dimensions = Dimensions::fromWidthAndHeight($width, $height);

        return [$width, $height];
    }

    /**
     * @param CatalogImageHelper $helper
     * @param bool $flag
     */
    public function beforeKeepFrame(CatalogImageHelper $helper, $flag)
    {
        $this->keepFrame = (bool)$flag;
    }

    /**
     * @param  CatalogImageHelper $helper
     * @param  \Closure           $originalMethod
     *
     * @return string
     */
    public function aroundGetUrl(CatalogImageHelper $helper, \Closure $originalMethod)
    {
        $imagePath = $this->imageFile ?: $this->product->getData($helper->getType());

        $image = $this->imageFactory->build(sprintf('catalog/product%s', $imagePath), $originalMethod);

        return $this->urlGenerator->generateFor(
            $image,
            $this->transformationModel->addFreeformTransformationForImage(
                $this->createTransformation($helper),
                $imagePath
            )
        );
    }

    /**
     * @param CatalogImageHelper $helper
     * @return Transformation
     */
    private function createTransformation(CatalogImageHelper $helper)
    {
        $dimensions = $this->dimensions ?: Dimensions::fromWidthAndHeight($helper->getWidth(), $helper->getHeight());

        $transform = $this->configuration->getDefaultTransformation()->withDimensions($dimensions);

        if ($this->keepFrame) {
            $transform->withCrop(Crop::fromString('pad'))
                ->withDimensions(Dimensions::squareMissingDimension($dimensions));
        } else {
            $transform->withCrop(Crop::fromString('fit'));
        }

        return $transform;
    }
}
