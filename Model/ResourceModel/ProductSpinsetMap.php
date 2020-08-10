<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel;

class ProductSpinsetMap extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('cloudinary_product_spinset_map', 'id');
    }
}
