<?php

namespace Cloudinary\Cloudinary\Core;

class Cloud
{

    private $cloudName;


    private function __construct($cloudName)
    {
        $this->cloudName = (string)$cloudName;
    }

    public static function fromName($aCloudName)
    {
        return new Cloud($aCloudName);
    }

    public function __toString()
    {
        return $this->cloudName;
    }
}
