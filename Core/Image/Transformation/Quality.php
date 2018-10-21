<?php

namespace Cloudinary\Cloudinary\Core\Image\Transformation;

class Quality
{
    private $value;

    private function __construct($value)
    {
        $this->value = $value;
    }

    public static function fromString($value)
    {
        return new Quality($value);
    }

    public function __toString()
    {
        return $this->value;
    }
}
