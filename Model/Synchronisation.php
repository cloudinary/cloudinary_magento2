<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Core\Image\Synchronizable;
use Cloudinary\Cloudinary\Model\ResourceModel\Synchronisation as SynchronisationResourceModel;
use Magento\Framework\Model\AbstractModel;

class Synchronisation extends AbstractModel implements Synchronizable
{
    protected function _construct()
    {
        $this->_init(SynchronisationResourceModel::class);
    }

    public function setImagePath($imagePath)
    {
        return $this->setData('image_path', $imagePath);
    }

    public function getImagePath()
    {
        return $this->getData('image_path');
    }

    public function getFilename()
    {
        return basename($this->getImagePath());
    }

    public function getRelativePath()
    {
        return $this->getImagePath();
    }

    public function tagAsSynchronized()
    {
        $this->save();
    }
}
