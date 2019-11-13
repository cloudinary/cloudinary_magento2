<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel\MediaLibraryMap;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            \Cloudinary\Cloudinary\Model\MediaLibraryMap::class,
            \Cloudinary\Cloudinary\Model\ResourceModel\MediaLibraryMap::class
        );
    }
}
