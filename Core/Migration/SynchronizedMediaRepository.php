<?php

namespace Cloudinary\Cloudinary\Core\Migration;

interface SynchronizedMediaRepository
{
    public function findUnsynchronisedImages();
    public function findOrphanedSynchronisedImages();
}
