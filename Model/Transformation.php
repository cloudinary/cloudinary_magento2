<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Core\Image\Transformation as ImageTransformation;
use Cloudinary\Cloudinary\Core\Image\Transformation\Freeform;
use Cloudinary\Cloudinary\Model\ResourceModel\Transformation as TransformationResourceModel;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Transformation extends AbstractModel
{
    /**
     * @var string
     */
    private $imageNameCacheKey;

    private $configuration;

    /**
     * @param Context          $context
     * @param Registry         $registry
     * @param Configuration    $configuration
     * @param AbstractResource $resource
     * @param AbstractDb       $resourceCollection
     * @param array            $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Configuration $configuration,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configuration = $configuration;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(TransformationResourceModel::class);
    }

    /**
     * @param  string $imageName
     * @return $this
     */
    public function setImageName($imageName)
    {
        return $this->setData('image_name', $imageName);
    }

    /**
     * @return string
     */
    public function getImageName()
    {
        return $this->getData('image_name');
    }

    /**
     * @param  string $transformation
     * @return $this
     */
    public function setFreeTransformation($transformation)
    {
        return $this->setData('free_transformation', $transformation);
    }

    /**
     * @return string
     */
    public function getFreeTransformation()
    {
        return $this->getData('free_transformation');
    }

    /**
     * @param  string $imageName
     * @return ImageTransformation
     */
    public function transformationForImage($imageName)
    {
        return $this->addFreeformTransformationForImage(
            $this->configuration->getDefaultTransformation(),
            $imageName
        );
    }

    /**
     * @param  ImageTransformation $transformation
     * @param  string              $imageName
     * @param  bool                $refresh
     * @return ImageTransformation
     */
    public function addFreeformTransformationForImage(ImageTransformation $transformation, $imageName, $refresh = false)
    {
        $this->imageNameCacheKey = 'cldfreetransformcachekey_' . (string) $imageName;
        if (!$refresh && ($cacheResult = $this->getFromCache()) !== null) {
            $model = $cacheResult;
        } else {
            $model = $this->cacheResult($this->load($imageName));
        }

        if ($model->getImageName() === $imageName && $model->hasFreeTransformation()) {
            $transformation->withFreeform(Freeform::fromString($model->getFreeTransformation()));
        }

        return $transformation;
    }

    /**
     * @return bool
     */
    private function hasFreeTransformation()
    {
        return !empty($this->getFreeTransformation());
    }

    /**
     * @method cacheResult
     * @param  bool        $result
     * @return mixed
     */
    private function cacheResult($result)
    {
        $this->_registry->unregister($this->imageNameCacheKey);
        $this->_registry->register($this->imageNameCacheKey, $result);
        return $result;
    }

    /**
     * @method cacheResult
     * @return mixed
     */
    private function getFromCache()
    {
        return $this->_registry->registry($this->imageNameCacheKey);
    }
}
