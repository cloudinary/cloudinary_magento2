<?php

namespace Cloudinary\Cloudinary\Core\Security;

use Cloudinary;
use Cloudinary\Cloudinary\Core\Cloud;
use Cloudinary\Cloudinary\Core\Credentials;

class CloudinaryEnvironmentVariable implements EnvironmentVariable
{

    private $environmentVariable;

    private function __construct($environmentVariable)
    {
        $this->environmentVariable = (string)$environmentVariable;
        try {
            Cloudinary::config_from_url(str_replace('CLOUDINARY_URL=', '', $environmentVariable));
        } catch (\Exception $e){
            throw new \Cloudinary\Cloudinary\Core\Exception\InvalidCredentials('Cloudinary config creation from environment variable failed');
        }
    }

    public static function fromString($environmentVariable)
    {
        return new CloudinaryEnvironmentVariable($environmentVariable);
    }

    public function getCloud()
    {
        return Cloud::fromName(Cloudinary::config_get('cloud_name'));
    }

    public function getCredentials()
    {
        return Credentials::fromKeyAndSecret(
            Key::fromString(Cloudinary::config_get('api_key')),
            Secret::fromString(Cloudinary::config_get('api_secret'))
        );
    }

    public function __toString()
    {
        return $this->environmentVariable;
    }

}
