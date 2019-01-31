<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary;
use Cloudinary\Api;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Symfony\Component\Console\Output\OutputInterface;

class BatchDownloader
{
    const API_REQUEST_MAX_RESULTS = 1;
    const API_REQUESTS_SLEEP_BEFORE_NEXT_CALL = 3; //Seconds
    const API_REQUEST_STOP_ON_REMAINING_RATE_LIMIT = 470;
    const WAIT_FOR_RATE_LIMIT_RESET_MESSAGE = "Cloudinary API - calls rate limit reached, sleeping until %s ...";
    const DONE_MESSAGE = "Done :)";

    /**
     * @var ConfigurationInterface
     */
    private $_configuration;

    /**
     * @var ConfigurationBuilder
     */
    private $_configurationBuilder;

    /**
     * @var DirectoryList
     */
    private $_directoryList;

    /**
     * @var Cloudinary\Api
     */
    private $_api;

    private $_nextCursor = null;

    private $_rateLimitResetAt = null;
    private $_rateLimitAllowed = null;
    private $_rateLimitRemaining = null;

    /**
     * ApiClient constructor.
     *
     * @param ConfigurationInterface $configuration
     * @param ConfigurationBuilder   $configurationBuilder
     * @param Api                    $api
     * @param DirectoryList          $directoryList
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        Api $api,
        DirectoryList $directoryList
    ) {
        $this->_configuration = $configuration;
        $this->_configurationBuilder = $configurationBuilder;
        $this->_api = $api;
        $this->_directoryList = $directoryList;
        if ($this->_configuration->isEnabled()) {
            $this->_authorise();
        }
    }

    private function _authorise()
    {
        Cloudinary::config($this->_configurationBuilder->build());
        Cloudinary::$USER_PLATFORM = $this->_configuration->getUserPlatform();
    }

    /**
     * Find unsynchronised images and upload them to cloudinary
     *
     * @param  OutputInterface|null $output
     * @return bool
     * @throws \Exception
     */
    public function downloadUnsynchronisedImages(OutputInterface $output = null)
    {
        do {
            try {
                $response = $this->getResources($this->_nextCursor);
                if (count($response->getResources()) > 0) {
                    foreach ($response->getResources() as $resource) {
                        //Download Images...
                    }
                } else {
                    $output->writeln('<info>' . self::DONE_MESSAGE . '</info>');
                    break;
                }
                if (($this->_nextCursor = $response->getNextCursor()) && (int)$this->_rateLimitRemaining <= self::API_REQUEST_STOP_ON_REMAINING_RATE_LIMIT) {
                    $output->writeln('<comment>' . sprintf(self::WAIT_FOR_RATE_LIMIT_RESET_MESSAGE, date('Y-m-d H:i:s', ($this->_rateLimitResetAt + 10))) . '</comment>');
                    @time_sleep_until($this->_rateLimitResetAt + 10);
                }
                sleep(self::API_REQUESTS_SLEEP_BEFORE_NEXT_CALL); //Wait between each API call.
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                break;
            }
        } while ($this->_nextCursor);
    }

    /**
     * @method getResources
     * @param  mixed       $nextCursor
     * @return DataObject
     */
    private function getResources($nextCursor = null)
    {
        $response = $this->_api->resources(
            [
            "resource_type" => 'image',
            "explicit" => DirectoryList::MEDIA,
            "max_results" => self::API_REQUEST_MAX_RESULTS,
            "next_cursor" => $nextCursor,
            ]
        );
        $this->_rateLimitResetAt = $response->rate_limit_reset_at;
        $this->_rateLimitAllowed = $response->rate_limit_allowed;
        $this->_rateLimitRemaining = $response->rate_limit_remaining;
        return new DataObject((array)$response);
    }
}
