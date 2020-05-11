<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel\ProductGalleryApiQueue;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            \Cloudinary\Cloudinary\Model\ProductGalleryApiQueue::class,
            \Cloudinary\Cloudinary\Model\ResourceModel\ProductGalleryApiQueue::class
        );
    }
}
