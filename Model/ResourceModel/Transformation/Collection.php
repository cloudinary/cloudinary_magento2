<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel\Transformation;

use Cloudinary\Cloudinary\Model\ResourceModel\Transformation as TransformationResourceModel;
use Cloudinary\Cloudinary\Model\Transformation as TransformationModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(TransformationModel::class, TransformationResourceModel::class);
    }
}
