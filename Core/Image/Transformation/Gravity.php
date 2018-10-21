<?php

namespace Cloudinary\Cloudinary\Core\Image\Transformation;

class Gravity
{
    private $value;

    private function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }

    public static function fromString($value)
    {
        return new Gravity($value);
    }

    public static function null()
    {
        return new Gravity(null);
    }
}


