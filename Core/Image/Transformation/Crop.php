<?php

namespace Cloudinary\Cloudinary\Core\Image\Transformation;

class Crop
{
    const PAD = 'pad';
    const LPAD = 'lpad';
    const FIT = 'fit';
    const LIMIT = 'limit';

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

    public static function lpad()
    {
        return new Crop(self::LPAD);
    }

    public static function fit()
    {
        return new Crop(self::FIT);
    }

    public static function limit()
    {
        return new Crop(self::LIMIT);
    }

    public function __toString()
    {
        return $this->value;
    }
}
