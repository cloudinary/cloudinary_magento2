<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Synchronisation extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('cloudinary_synchronisation', 'cloudinary_synchronisation_id');
    }
}
