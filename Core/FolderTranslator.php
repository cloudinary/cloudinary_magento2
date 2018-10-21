<?php

namespace Cloudinary\Cloudinary\Core;

/**
 * Interface FolderTranslator
 *
 * Supposed to contain the logic of which folder should a file be uploaded in cloudinary.
 *
 * @package Cloudinary\Cloudinary\Core\Migration
 */
interface FolderTranslator
{
    public function translate($path);
    public function reverse($folder);
}
