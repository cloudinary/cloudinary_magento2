<?php

namespace Cloudinary\Cloudinary\Core\Security;

class Secret
{

    private $secret;

    private function __construct($secret)
    {
        $this->secret = (string)$secret;
    }

    public static function fromString($aSecret)
    {
        return new Secret($aSecret);
    }

    public function __toString()
    {
        return $this->secret;
    }
}
