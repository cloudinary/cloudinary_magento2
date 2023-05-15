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

    /**
     * @method getCloudinaryMLOptions
     * @param bool $multiple Allow multiple
     * @param bool $refresh Refresh options
     * @return array
     */
    public function getCloudinaryMLOptions($multiple = false, $refresh = true)
    {
        if ((is_null($this->cloudinaryMLoptions) || $refresh) && $this->configuration->isEnabled()) {
            $this->cloudinaryMLoptions = [];
            $this->timestamp = time();
            $this->credentials = $this->configuration->getCredentials();
            if (!$this->credentials["cloud_name"] || !$this->credentials["api_key"] || !$this->credentials["api_secret"]) {
                $this->credentials = null;
            } else {
                $this->cloudinaryMLoptions = [
                    'cloud_name' => $this->credentials["cloud_name"],
                    'api_key' => $this->credentials["api_key"],
                    'cms_type' => 'magento',
                    //'default_transformations' => [['quality' => 'auto'],['format' => 'auto']],
                    'integration' => [
                        'type' => 'magento_plugin',
                        'version' => $this->configuration->getModuleVersion(),
                        'platform' => "{$this->configuration->getMagentoPlatformName()} {$this->configuration->getMagentoPlatformEdition()} {$this->configuration->getMagentoPlatformVersion()}"
                    ]
                ];
            }
        }
        if ($this->cloudinaryMLoptions) {
            $this->cloudinaryMLoptions['multiple'] = $multiple;
        }

        return $this->cloudinaryMLoptions;
    }

    /**
     * @method getCloudinaryMLshowOptions
     * @param  string|null $resourceType
     * @param  string $path
     * @return [type]
     */
    public function getCloudinaryMLshowOptions($resourceType = null, $path = "")
    {
        $options = [];
        if ($resourceType || $resourceType) {
            $options["folder"] = [
                "path" => $path,
                "resource_type" => $resourceType,
            ];
        }
        return $options;
    }

    /**
     * @return null
     */
    public function getCname()
    {
         $cname = ($this->configuration->getCredentials()['cname']) ? $this->configuration->getCredentials()['cname'] : null;
         return $cname;
    }
}
