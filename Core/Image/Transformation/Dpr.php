<?php

namespace Cloudinary\Cloudinary\Core\Image\Transformation;

class Dpr
{
    private $value;

    private function __construct($value)
    {
        $this->value = $value;
    }

    public static function fromString($value)
    {
        return new Dpr($value);
    }

    public function __toString()
    {
        return $this->value;
    }
}
