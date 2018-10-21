<?php

namespace Cloudinary\Cloudinary\Ui\DataProvider\Product\Form\Modifier;

use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Cloudinary\Core\CloudinaryImageProvider;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Cloudinary\Core\Image\Transformation\Freeform;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form;
use Cloudinary\Cloudinary\Model\TransformationFactory;
use Magento\Catalog\Model\Product\Gallery\Entry;
use Magento\Framework\UrlInterface;

class Product extends AbstractModifier
{
    const GROUP_CLOUDINARY = 'cloudinary';
    const GROUP_CONTENT = 'content';
    const DATA_SCOPE_REVIEW = 'grouped';
    const SORT_ORDER = 55;
    const LINK_TYPE = 'associated';

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var TransformationFactory
     */
    protected $transformationFactory;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * @var CloudinaryImageProvider
     */
    protected $cloudinaryImageProvider;

    /**
     * @param LocatorInterface $locator
     * @param TransformationFactory $transformationFactory
     */
    public function __construct(
        LocatorInterface $locator,
        TransformationFactory $transformationFactory,
        UrlInterface $urlBuilder,
        ConfigurationInterface $configuration,
        CloudinaryImageProvider $cloudinaryImageProvider
    ) {
        $this->locator = $locator;
        $this->transformationFactory = $transformationFactory;
        $this->urlBuilder = $urlBuilder;
        $this->configuration = $configuration;
        $this->cloudinaryImageProvider = $cloudinaryImageProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if (!$this->isCloudinaryModuleActive()) {
            return $meta;
        }

        $meta[static::GROUP_CLOUDINARY] = [
            'children' => [
                'cloudinary' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Cloudinary_Cloudinary/js/product_free_transform',
                                'breakLine' => false,
                                'sortOrder' => self::SORT_ORDER,
                                'dataScope' => '',
                                'imports' => [
                                    'mediaGallery' => '${ $.provider }:data.product.cloudinary_transforms',
                                    'ajaxUrl' => '${ $.provider }:data.product.cloudinary_ajax_url'
                                ]
                            ]
                        ]
                    ],
                ],
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Cloudinary'),
                        'collapsible' => true,
                        'opened' => false,
                        'componentType' => Form\Fieldset::NAME,
                        'sortOrder' =>
                            $this->getNextGroupSortOrder(
                                $meta,
                                static::GROUP_CONTENT,
                                static::SORT_ORDER
                            ),
                    ],
                ],
            ],
        ];

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        if (!$this->isCloudinaryModuleActive()) {
            return $data;
        }

        $product = $this->locator->getProduct();
        $id = $product->getId();

        $data[$id][self::DATA_SOURCE_DEFAULT]['cloudinary_transforms'] = $this->extractData(
            $this->injectImageUrls(
                $this->injectFreeTransformations(
                    $this->filterNonImageTypes(
                        $product->getMediaGalleryEntries() ?: []
                    )
                )
            )
        );

        $data[$id][self::DATA_SOURCE_DEFAULT]['cloudinary_ajax_url'] = $this->urlBuilder->getUrl(
            'cloudinary/ajax_free/image'
        );

        return $data;
    }

    /**
     * @return bool
     */
    private function isCloudinaryModuleActive()
    {
        return $this->configuration->isEnabled() && $this->configuration->hasEnvironmentVariable();
    }

    /**
     * @param [Entry]
     * @return [Entry]
     */
    private function extractData(array $images)
    {
        return array_map(
            function($media) {
                return $media->getData();
            },
            $images
        );
    }

    /**
     * @param [Entry]
     * @return [Entry]
     */
    private function filterNonImageTypes(array $images)
    {
        return array_filter(
            $images,
            function($image) {
                return $image->getMediaType() === 'image';
            }
        );
    }

    /**
     * @param [Entry]
     * @return [Entry]
     */
    private function injectFreeTransformations(array $images)
    {
        foreach ($images as $image) {
            $model = $this->transformationFactory->create();
            $model->load($image->getFile());
            $image->setFreeTransformation($model->getFreeTransformation());
        }

        return $images;
    }

    /**
     * @param [Entry]
     * @return [Entry]
     */
    private function injectImageUrls(array $images)
    {
        foreach ($images as $image) {
            $url = $this->cloudinaryImageProvider->retrieveTransformed(
                Image::fromPath(
                    $image->getFile(),
                    $this->configuration->getMigratedPath(sprintf('catalog/product/%s', $image->getFile()))
                ),
                $this->defaultTransformWithFreeTransform($image->getFreeTransformation())
            );

            $image->setImageUrl((string)$url);
        }

        return $images;
    }

    /**
     * @param string $freeTransform
     * @return Transformation
     */
    private function defaultTransformWithFreeTransform($freeTransform)
    {
        $transformation = $this->configuration->getDefaultTransformation();

        if ($freeTransform) {
            $transformation->withFreeform(Freeform::fromString($freeTransform));
        }

        return $transformation;
    }
}
