<?php

namespace Cloudinary\Cloudinary\Core;

use Cloudinary\Cloudinary\Core\Exception\FileExists;

class UploadResponseValidator
{
    public function validateResponse($image, $uploadResponse)
    {
        if ($uploadResponse['existing'] == 1) {
            FileExists::throwWith($image);
        }

        return $uploadResponse;
    }
}
