<?php

namespace Cloudinary\Cloudinary\Core\Image\Transformation;

class Freeform
{
    /**
     * @var string
     */
    private $urlParameters;

    /**
     * Freeform constructor.
     * @param string $urlParameters
     */
    public function __construct($urlParameters)
    {
        $this->urlParameters = $urlParameters;
    }

    /**
     * @param string $value
     * @return Freeform
     */
    public static function fromString($value)
    {
        return new Freeform($value);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->urlParameters;
    }
}
