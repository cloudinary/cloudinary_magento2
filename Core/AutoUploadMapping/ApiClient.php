<?php

namespace Cloudinary\Cloudinary\Core\AutoUploadMapping;

use Cloudinary;
use Cloudinary\Api;
use Cloudinary\Api\Response;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;

class ApiClient
{
    const MAPPINGS_KEY = 'mappings';
    const FOLDER_KEY = 'folder';
    const URL_KEY = 'template';

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
     * ApiClient constructor.
     * @param ConfigurationInterface $configuration
     * @param ConfigurationBuilder $configurationBuilder
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        Api $api
    ) {
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
        $this->api = $api;
        if ($this->configuration->isEnabled()) {
            $this->authorise();
        }
    }

    /**
     * @param ConfigurationInterface $configuration
     * @return ApiClient
     */
    public static function fromConfiguration(ConfigurationInterface $configuration)
    {
        return new ApiClient(
            $configuration,
            new ConfigurationBuilder($configuration),
            new Api()
        );
    }

    /**
     * @param string $folder
     * @param string $url
     * @return bool
     */
    public function prepareMapping($folder, $url)
    {
        try {

            $existingMappings = $this->parseFetchMappingsResponse($this->api->upload_mappings());

            if ($this->hasMapping($existingMappings, $folder)) {
                if (!$this->mappingMatches($existingMappings, $folder, $url)) {
                    $this->api->update_upload_mapping($folder, [self::URL_KEY => $url]);
                }
            } else {
                $this->api->create_upload_mapping($folder, [self::URL_KEY => $url]);
            }

            return true;

        } catch (\Exception $e) {
            $this->errors[] = $e;
            return false;
        }
    }

    /**
     * @param Response $response
     * @return array
     * @throws \Exception
     */
    private function parseFetchMappingsResponse(Response $response)
    {
        if (!array_key_exists(self::MAPPINGS_KEY, $response) || !is_array($response[self::MAPPINGS_KEY])) {
            throw new \Exception('Illegal mapping response');
        }

        return $response[self::MAPPINGS_KEY];
    }

    /**
     * @param array $mappings
     * @param string $folder
     * @return array
     */
    private function filterMappings(array $mappings, $folder)
    {
        return array_filter(
            $mappings,
            function(array $mapping) use ($folder) {
                return $mapping[self::FOLDER_KEY] == $folder;
            }
        );
    }

    /**
     * @param array $mappings
     * @param string $folder
     * @return bool
     */
    private function hasMapping(array $mappings, $folder)
    {
        return count($this->filterMappings($mappings, $folder)) > 0;
    }

    /**
     * @param array $existingMappings
     * @param string $folder
     * @param string $url
     * @return bool
     */
    private function mappingMatches(array $existingMappings, $folder, $url)
    {
        return count(
            array_filter(
                $this->filterMappings($existingMappings, $folder),
                function(array $mapping) use ($url) {
                    return $mapping[self::URL_KEY] == $url;
                }
            )
        ) > 0;
    }

    private function authorise()
    {
        Cloudinary::config($this->configurationBuilder->build());
        Cloudinary::$USER_PLATFORM = $this->configuration->getUserPlatform();
    }

    /**
     * @return [Exception]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
