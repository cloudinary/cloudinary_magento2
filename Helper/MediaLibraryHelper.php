<?php

namespace Cloudinary\Cloudinary\Helper;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Framework\App\Helper\Context;

class MediaLibraryHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * Cloudinary credentials
     * @var array|null
     */
    protected $credentials;

    /**
     * Current timestamp
     * @var int|null
     */
    protected $timestamp;

    /**
     * Sugnature
     * @var string|null
     */
    protected $signature;

    /**
     * Cloudinary ML Options
     * @var array|null
     */
    protected $cloudinaryMLoptions;

    /**
     * @param Context $context
     * @param ConfigurationInterface $configuration
    */
    public function __construct(
        Context $context,
        ConfigurationInterface $configuration
    ) {
        parent::__construct($context);
        $this->configuration = $configuration;
    }

    public function getCloudinaryMLOptions($refresh = false)
    {
        if ((is_null($this->cloudinaryMLoptions) || $refresh) && $this->configuration->isEnabled()) {
            $this->cloudinaryMLoptions = [];
            $this->timestamp = time();
            $this->credentials = [
                "cloud_name" => (string)$this->configuration->getCloud(),
                "api_key" => (string)$this->configuration->getCredentials()->getKey(),
                "api_secret" => (string)$this->configuration->getCredentials()->getSecret()
            ];
            if (!$this->credentials["cloud_name"] || !$this->credentials["api_key"] || !$this->credentials["api_secret"]) {
                $this->credentials = null;
            } else {
                $this->cloudinaryMLoptions = [
                    'cloud_name' => $this->credentials["cloud_name"],
                    'api_key' => $this->credentials["api_key"],
                    'cms_type' => 'magento',
                    'multiple' => true,
                    //'default_transformations' => [['quality' => 'auto'],['format' => 'auto']],
                ];
                if (($this->credentials["username"] = $this->configuration->getAutomaticLoginUser())) {
                    $this->cloudinaryMLoptions["timestamp"] = $this->timestamp;
                    $this->cloudinaryMLoptions["username"] = $this->credentials["username"];
                    $this->cloudinaryMLoptions["signature"] = $this->signature = hash('sha256', urldecode(http_build_query([
                        'cloud_name' => $this->credentials['cloud_name'],
                        'timestamp'  => $this->timestamp,
                        'username'   => $this->credentials['username'],
                    ])) . $this->credentials['api_secret']);
                }
            }
        }

        return $this->cloudinaryMLoptions;
    }
}
