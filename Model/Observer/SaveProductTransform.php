<?php

namespace Cloudinary\Cloudinary\Model\Observer;

use Cloudinary\Cloudinary\Helper\Product\Free as Helper;
use Cloudinary\Cloudinary\Model\TransformationFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveProductTransform implements ObserverInterface
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var TransformationFactory
     */
    private $transformationFactory;

    /**
     * @param Helper $helper
     * @param TransformationFactory $transformationFactory
     */
    public function __construct(Helper $helper, TransformationFactory $transformationFactory)
    {
        $this->helper = $helper;
        $this->transformationFactory = $transformationFactory;
    }

    /**
     * @param  Observer $observer
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();
        $mediaGalleryImages = $this->helper->getMediaGalleryImages($product);

        $changedTransforms = $this->helper->filterUpdated(
            $product->getCloudinaryFreeTransform(),
            $product->getCloudinaryFreeTransformChanges()
        );

        foreach ($changedTransforms as $id => $transform) {
            $this->storeFreeTransformation($this->helper->getImageNameForId($id, $mediaGalleryImages), $transform);
        }
    }

    /**
     * @param string $imageName
     * @param string $transform
     */
    private function storeFreeTransformation($imageName, $transform)
    {
        $this->transformationFactory->create()
            ->setImageName($imageName)
            ->setFreeTransformation($transform)
            ->save();
    }
}
