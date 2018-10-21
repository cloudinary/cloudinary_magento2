<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Transformation extends AbstractDb
{
    protected $_isPkAutoIncrement = false;

    protected function _construct()
    {
        $this->_init('cloudinary_transformation', 'image_name');
    }
}
