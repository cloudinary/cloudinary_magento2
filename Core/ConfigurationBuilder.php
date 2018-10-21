<?php

namespace Cloudinary\Cloudinary\Core;

class ConfigurationBuilder
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function build()
    {
        $config = [
            "cloud_name" => (string)$this->configuration->getCloud(),
            "api_key" => (string)$this->configuration->getCredentials()->getKey(),
            "api_secret" => (string)$this->configuration->getCredentials()->getSecret()
        ];

        if ($this->configuration->getCdnSubdomainStatus()) {
            $config['cdn_subdomain'] = true;
        }
        return $config;
    }
}
