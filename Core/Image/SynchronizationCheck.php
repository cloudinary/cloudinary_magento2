<?php

namespace Cloudinary\Cloudinary\Core\Image;

interface SynchronizationCheck
{
    /**
     * @return boolean
     */
    public function isSynchronized($imageName);
}
