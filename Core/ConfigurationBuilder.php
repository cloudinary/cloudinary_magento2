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
        $credentials = $this->configuration->getCredentials();
        if ($credentials) {
            $cloud = [
                'cloud_name' => $credentials['cloud_name'],
                'api_key' =>  $credentials['api_key'],
                'api_secret' => $credentials['api_secret']
            ];

            $url = array_diff($credentials, $cloud);

            $config = array('cloud' => $cloud);

            if ($url && is_array($url)) {
                $config['url'] = $url;
            }
            /**
             * This feature is deprecated and will be removed in the next major version.
             *
             * @deprecated will be removed in the next major version.
             */
            if ($this->configuration->getCdnSubdomainStatus()) {
                $config['cloud']['cdn_subdomain'] = true;
            }

            return $config;
        }
        return $this;
    }
}
