<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel;

class ProductGalleryApiQueue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('cloudinary_product_gallery_api_queue', 'id');
    }
}
