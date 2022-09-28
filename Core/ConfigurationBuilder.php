<?php

namespace Cloudinary\Cloudinary\Core;
use Cloudinary\Configuration\Configuration;

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

    /**
     * @return Configuration instance
     */
    public function build()
    {

        $reg = $this->configuration->getCoreRegistry();


        $config = array('cloud' => $this->configuration->getCredentials());

        if ($this->configuration->getCdnSubdomainStatus()) {
            $config['cloud']['cdn_subdomain'] = true;
        }

        return $config;
    }
}
