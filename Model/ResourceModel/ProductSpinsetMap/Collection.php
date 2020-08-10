<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel\ProductSpinsetMap;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            \Cloudinary\Cloudinary\Model\ProductSpinsetMap::class,
            \Cloudinary\Cloudinary\Model\ResourceModel\ProductSpinsetMap::class
        );
    }
}
