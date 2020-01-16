<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel\ProductVideo;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            \Cloudinary\Cloudinary\Model\ProductVideo::class,
            \Cloudinary\Cloudinary\Model\ResourceModel\ProductVideo::class
        );
    }
}
