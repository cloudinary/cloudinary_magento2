<?php

namespace Cloudinary\Cloudinary\Model\Observer;

use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Cloudinary\Cloudinary\Model\ProductImageFinder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UploadProductImage implements ObserverInterface
{
    /**
     * @var ProductImageFinder
     */
    private $productImageFinder;

    /**
     * @var CloudinaryImageManager
     */
    private $cloudinaryImageManager;

    /**
     * @param ProductImageFinder $productImageFinder
     * @param CloudinaryImageManager $cloudinaryImageManager
     */
    public function __construct(
        ProductImageFinder $productImageFinder,
        CloudinaryImageManager $cloudinaryImageManager
    ) {
        $this->productImageFinder = $productImageFinder;
        $this->cloudinaryImageManager = $cloudinaryImageManager;
    }

    /**
     * @param  Observer $observer
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        foreach ($this->productImageFinder->findNewImages($product) as $image) {
            $this->cloudinaryImageManager->uploadAndSynchronise($image);
        }
    }
}
