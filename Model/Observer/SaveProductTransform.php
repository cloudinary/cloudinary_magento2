<?php

namespace Cloudinary\Cloudinary\Model\Observer;

use Cloudinary\Cloudinary\Helper\Product\Free as Helper;
use Cloudinary\Cloudinary\Model\TransformationFactory;
use Magento\Framework\App\ResourceConnection;
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
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @method __construct
     * @param  Helper                $helper
     * @param  TransformationFactory $transformationFactor
     * @param  ResourceConnection    $resourceConnection
     */
    public function __construct(
        Helper $helper,
        TransformationFactory $transformationFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->helper = $helper;
        $this->transformationFactory = $transformationFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();
        $mediaGalleryImages = $this->helper->getMediaGalleryImages($product);

        $changedTransforms = $this->helper->filterUpdated(
            $product->getCloudinaryFreeTransform(),
            $product->getCloudinaryFreeTransformChanges()
        );

        foreach ($mediaGalleryImages as $gallItemId => $gallItem) {
            if (isset($gallItem['cldspinset']) && $gallItem['media_type'] === 'image') {
                $this->resourceConnection->getConnection()
                    ->insertOnDuplicate($this->resourceConnection->getTableName('cloudinary_product_spinset_map'), [
                        'image_name' => $gallItem['file'],
                        'cldspinset' => $gallItem['cldspinset']
                    ], ['image_name', 'cldspinset']);
            }
        }
        foreach ($changedTransforms as $id => $transform) {
            $this->storeFreeTransformation($this->helper->getImageNameForId($id, $mediaGalleryImages), $transform);
        }

        foreach ($changedTransforms as $id => $transform) {
            $this->helper->validate($this->helper->getImageNameForId($id, $mediaGalleryImages), $transform);
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
