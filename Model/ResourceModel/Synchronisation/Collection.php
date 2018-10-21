<?php

namespace Cloudinary\Cloudinary\Model\ResourceModel\Synchronisation;

use Cloudinary\Cloudinary\Model\ResourceModel\Synchronisation as SynchronisationResourceModel;
use Cloudinary\Cloudinary\Model\Synchronisation as SynchronisationModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(SynchronisationModel::class, SynchronisationResourceModel::class);
    }
}
