<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel;

class MediaLibraryMap extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('cloudinary_media_library_map', 'id');
    }
}
