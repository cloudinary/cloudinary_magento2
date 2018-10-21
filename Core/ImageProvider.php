<?php

namespace Cloudinary\Cloudinary\Core;

use Cloudinary\Cloudinary\Core\Image\Transformation;

interface ImageProvider
{
    public function upload(Image $image);
    public function retrieveTransformed(Image $image, Transformation $transformation);
    public function retrieve(Image $image);
    public function delete(Image $image);
    public function validateCredentials();
}
