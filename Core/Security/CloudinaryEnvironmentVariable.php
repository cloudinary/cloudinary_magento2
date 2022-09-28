<?php

namespace Cloudinary\Cloudinary\Core\Security;

use Cloudinary\Cloudinary;
use Cloudinary\Cloudinary\Core\Credentials;
use Cloudinary\Configuration\Configuration;

class CloudinaryEnvironmentVariable implements EnvironmentVariable
{
    /**
     * @var array|string|string[]
     */
    private $environmentVariable;

    /**
     * @var $configuration
     */
    private $configuration;

    /**
     * @param $environmentVariable
     * @throws Cloudinary\Core\Exception\InvalidCredentials
     */
    private function __construct($environmentVariable)
    {
        $this->environmentVariable = (string)$environmentVariable;
        try {
            //Cloudinary::config_from_url(str_replace('CLOUDINARY_URL=', '', $environmentVariable));
            $this->environmentVariable = str_replace('CLOUDINARY_URL=', '', $environmentVariable);
        } catch (\Exception $e) {
            throw new \Cloudinary\Cloudinary\Core\Exception\InvalidCredentials('Cloudinary config creation from environment variable failed');
        }
    }

    /**
     * @param $environmentVariable
     * @return CloudinaryEnvironmentVariable
     * @throws Cloudinary\Core\Exception\InvalidCredentials
     */
    public static function fromString($environmentVariable)
    {
        return new CloudinaryEnvironmentVariable($environmentVariable);
    }

    /**
     * @return string
     */
    public function getCloud()
    {
        if (!$this->configuration) {
            if (!$this->environmentVariable) {
                return false;
            }
            $this->configuration = new Configuration($this->environmentVariable);
        }
        return $this->configuration->cloud->cloudName;
    }

    /**
     * @return Credentials
     */
    public function getCredentials()
    {
        if (!$this->configuration) {
            $this->configuration = new Configuration($this->environmentVariable);
        }

        return Credentials::fromKeyAndSecret(
            Key::fromString($this->configuration->cloud->apiKey),
            Secret::fromString($this->configuration->cloud->apiSecret)
        );
    }

    /**
     * @return array|string|string[]
     */
    public function __toString()
    {
        return $this->environmentVariable;
    }
}
