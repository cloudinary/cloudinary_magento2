<?php

namespace Cloudinary\Cloudinary\Core\Security;

class Key
{

    private $key;

    private function __construct($key)
    {
        $this->key = (string)$key;
    }

    public static function fromString($aKey)
    {
        return new Key($aKey);
    }

    public function __toString()
    {
        return $this->key;
    }

}
