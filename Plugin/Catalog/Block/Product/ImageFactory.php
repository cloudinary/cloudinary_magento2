<?php

declare(strict_types=1);

namespace Cloudinary\Cloudinary\Plugin\Catalog\Block\Product;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image\ImageFactory as CloudinaryImageFactory;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Core\Image\Transformation\Crop;
use Cloudinary\Cloudinary\Core\Image\Transformation\Dimensions;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Cloudinary\Model\Transformation as TransformationModel;
use Cloudinary\Cloudinary\Model\TransformationFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\Image as ImageBlock;
use Magento\Catalog\Block\Product\ImageFactory as CatalogImageFactory;
use Magento\Catalog\Model\Product;

class ImageFactory
{
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
     * @param CloudinaryImageFactory $cloudinaryImageFactory
     * @param UrlGenerator $urlGenerator
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        CloudinaryImageFactory $cloudinaryImageFactory,
        UrlGenerator $urlGenerator,
        ConfigurationInterface $configuration,
        TransformationFactory $transformationFactory
    ) {
        $this->cloudinaryImageFactory = $cloudinaryImageFactory;
        $this->urlGenerator = $urlGenerator;
        $this->configuration = $configuration;
        $this->transformationModel = $transformationFactory->create();
        $this->dimensions = null;
        $this->imageFile = null;
        $this->keepFrame = true;
    }

    /**
     * Create image block from product
     * @param CatalogImageFactory $catalogImageFactory
     * @param callable $proceed
     * @param Product $product
     * @param string $imageId
     * @param array|null $attributes
     * @return ImageBlock
     */
    public function aroundCreate(CatalogImageFactory $catalogImageFactory, callable $proceed, Product $product, string $imageId, array $attributes = null): ImageBlock
    {
        $imageBlock = $proceed($product, $imageId, $attributes);
        if (!$this->configuration->isEnabled()) {
            return $imageBlock;
        }

        try {
            if (strpos($imageBlock->getImageUrl(), $this->configuration->getMediaBaseUrl()) === 0) {
                $imagePath = preg_replace('/^' . preg_quote($this->configuration->getMediaBaseUrl(), '/') . '/', '', $imageBlock->getImageUrl());
                $imagePath = preg_replace('/\/cache\/[a-f0-9]{32}\//', '/', $imagePath);
                $image = $this->cloudinaryImageFactory->build($imagePath, $proceed);
                $generatedImageUrl = $this->urlGenerator->generateFor(
                    $image,
                    $this->transformationModel->addFreeformTransformationForImage(
                        $this->createTransformation($imageBlock),
                        $imagePath
                    )
                );
                $imageBlock->setOriginalImageUrl($imageBlock->setImageUrl());
                $imageBlock->setImageUrl($generatedImageUrl);
            }
        } catch (\Exception $e) {
            $imageBlock = $proceed($product, $imageId, $attributes);
        }

        return $imageBlock;
    }

    /**
     * @param ImageBlock $imageBlock
     * @return Transformation
     */
    private function createTransformation(ImageBlock $imageBlock)
    {
        $dimensions = $this->dimensions ?: Dimensions::fromWidthAndHeight($imageBlock->getWidth(), $imageBlock->getHeight());

        $transform = $this->configuration->getDefaultTransformation()->withDimensions($dimensions);

        if ($this->keepFrame) {
            $transform->withCrop(Crop::fromString('lpad'))
                ->withDimensions(Dimensions::squareMissingDimension($dimensions));
        } else {
            $transform->withCrop(Crop::fromString('fit'));
        }

        return $transform;
    }
}
