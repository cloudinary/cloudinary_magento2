<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Model\ResourceModel\Transformation as TransformationResourceModel;
use Cloudinary\Cloudinary\Model\Configuration;
use Cloudinary\Cloudinary\Core\Image\Transformation\Freeform;
use Cloudinary\Cloudinary\Core\Image\Transformation as ImageTransformation;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Transformation extends AbstractModel
{
    private $configuration;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Configuration $configuration
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Configuration $configuration,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
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
     * @param string $imageName
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
     * @param string $transformation
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
     * @param string $imageFile
     * @return ImageTransformation
     */
    public function transformationForImage($imageFile)
    {
        return $this->addFreeformTransformationForImage(
            $this->configuration->getDefaultTransformation(),
            $imageFile
        );
    }

    /**
     * @param ImageTransformation $transformation
     * @param string $imageFile
     * @return ImageTransformation
     */
    public function addFreeformTransformationForImage(ImageTransformation $transformation, $imageFile)
    {
        $this->load($imageFile);
        if (($this->getImageName() === $imageFile) && $this->hasFreeTransformation()) {
            $transformation->withFreeform(Freeform::fromString($this->getFreeTransformation()));
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
}
