<?php

namespace Cloudinary\Cloudinary\Core\AutoUploadMapping;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\BaseApiClient;
use Cloudinary\Cloudinary;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;

class ApiClient
{
    const MAPPINGS_KEY = 'mappings';
    const FOLDER_KEY = 'folder';
    const URL_KEY = 'template';

    private $cloudinarySDK;

    private $api;

    /**
     * @var bool
     */
    private $_authorised;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var ConfigurationBuilder
     */
    private $configurationBuilder;

    /**
     * @var [Exception]
     */
    private $errors = [];

    /**
     * @param ConfigurationInterface $configuration
     * @param ConfigurationBuilder $configurationBuilder
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder
    ) {
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
    }

    /**
     * @param  ConfigurationInterface $configuration
     * @return ApiClient
     */
    public static function fromConfiguration(ConfigurationInterface $configuration)
    {
        return new ApiClient(
            $configuration,
            new ConfigurationBuilder($configuration),
            new AdminApi()
        );
    }

    /**
     * @param  string $folder
     * @param  string $url
     * @return bool
     */
    public function prepareMapping($folder, $url)
    {
        try {
            $this->authorise();
            $existingMappings = $this->parseFetchMappingsResponse($this->api->uploadMappings());


            if ($this->hasMapping($existingMappings, $folder)) {
                if (!$this->mappingMatches($existingMappings, $folder, $url)) {
                    $this->api->updateUploadMapping($folder, [self::URL_KEY => $url]);
                }
            } else {
                $this->api->createUploadMapping($folder, [self::URL_KEY => $url]);
            }

            return true;
        } catch (\Exception $e) {
            $this->errors[] = $e;
            return false;
        }
    }

    /**
     * @param  ApiResponse $response
     * @return array
     * @throws \Exception
     */
    private function parseFetchMappingsResponse(ApiResponse $response)
    {
        $response = (array)$response;
        if (!array_key_exists(self::MAPPINGS_KEY, $response) || !is_array($response[self::MAPPINGS_KEY])) {
            throw new \Exception('Illegal mapping response');
        }

        return $response[self::MAPPINGS_KEY];
    }

    /**
     * @param  array  $mappings
     * @param  string $folder
     * @return array
     */
    private function filterMappings(array $mappings, $folder)
    {
        return array_filter(
            $mappings,
            function (array $mapping) use ($folder) {
                return $mapping[self::FOLDER_KEY] == $folder;
            }
        );
    }

    /**
     * @param  array  $mappings
     * @param  string $folder
     * @return bool
     */
    private function hasMapping(array $mappings, $folder)
    {
        return count($this->filterMappings($mappings, $folder)) > 0;
    }

    /**
     * @param  array  $existingMappings
     * @param  string $folder
     * @param  string $url
     * @return bool
     */
    private function mappingMatches(array $existingMappings, $folder, $url)
    {
        return count(
                array_filter(
                    $this->filterMappings($existingMappings, $folder),
                    function (array $mapping) use ($url) {
                        return $mapping[self::URL_KEY] == $url;
                    }
                )
            ) > 0;
    }

    private function authorise()
    {
        if (!$this->_authorised && $this->configuration->isEnabled()) {
            // $config = new Configuration($this->configurationBuilder->build());
            $this->cloudinarySDK = new Cloudinary($this->configurationBuilder->build());
            $this->api = $this->cloudinarySDK->adminApi();

            BaseApiClient::$userPlatform = $this->configuration->getUserPlatform();
            $this->_authorised = true;

            return $this;
        }
    }

    /**
     * @return [Exception]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
