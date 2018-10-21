<?php

namespace Cloudinary\Cloudinary\Core;

interface SynchroniseAssetsRepositoryInterface
{
    /**
     * @param string $imagePath
     * @return mixed
     */
    public function saveAsSynchronized($imagePath);

    /**
     * @param string g$imagePath
     * @return mixed
     */
    public function removeSynchronised($imagePath);
}
