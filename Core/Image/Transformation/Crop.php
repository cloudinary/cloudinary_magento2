<?php

namespace Cloudinary\Cloudinary\Core\Image\Transformation;

class Crop
{
    const PAD = 'pad';
    const FIT = 'fit';

    private $value;

    private function __construct($value)
    {
        $this->value = $value;
    }

    public static function fromString($value)
    {
        return new Crop($value);
    }

    public static function pad()
    {
        return new Crop(self::PAD);
    }

    public static function fit()
    {
        return new Crop(self::FIT);
    }

    public function __toString()
    {
        return $this->value;
    }
}
