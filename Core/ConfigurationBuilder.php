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
     * CLOUDINARY_URL=cloudinary://667623344116464:cKLIAxkU1_iPW8OCjumZ4E2i51A@m2clduat
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
