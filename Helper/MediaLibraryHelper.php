<?php

namespace Cloudinary\Cloudinary\Helper;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\Client\Curl;

class MediaLibraryHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Curl
     */
    protected $curl;

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
     * @param RequestInterface $request
     * @param Curl $curl
    */
    public function __construct(
        Context $context,
        ConfigurationInterface $configuration,
        RequestInterface $request,
        Curl $curl
    ) {
        parent::__construct($context);
        $this->configuration = $configuration;
        $this->request = $request;
        $this->curl = $curl;
    }

    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * @method getCloudinaryMLOptions
     * @param string|null $resourceType Resource Types: "image"/"video" or null for "all".
     * @param bool $refresh Refresh options
     * @return array
     */
    public function getCloudinaryMLOptions($resourceType = null, $refresh = false)
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
                if (in_array($resourceType, ["image","video"])) {
                    $this->cloudinaryMLoptions["folder"] = [
                            "path" => "",
                            "resource_type" => $resourceType
                    ];
                }
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

    public function convertRequestAssetUrlToImage()
    {
        if ($this->configuration->isEnabled()) {
            $asset = $this->getRequest()->getPostValue("asset");
            $this->curl->get($asset['secure_url']);
            $asset['image'] = $this->curl->getBody();
            $this->getRequest()->setPostValue("asset", $asset);

            $tmpfile = tmpfile();
            fwrite($tmpfile, $asset['image']);
            chmod(stream_get_meta_data($tmpfile)['uri'], 0644);

            $_FILES[$this->getRequest()->getParam('param_name', 'image')] = [
                "name" => basename($asset['url']),
                "type" => "{$asset['resource_type']}/{$asset['format']}",
                "tmp_name" => stream_get_meta_data($tmpfile)['uri'],
                "error" => 0,
                "size" => $asset['bytes'],
            ];

            return $tmpfile;
        }
    }
}
