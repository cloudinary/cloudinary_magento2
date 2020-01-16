<?php

namespace Cloudinary\Cloudinary\Cron;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Model\Api\ResourcesManagement;
use Cloudinary\Cloudinary\Model\ProductVideoFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class VideoDataImport
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var ResourcesManagement
     */
    private $cloudinaryResourcesManagement;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @param ProductVideoFactory
     */
    private $productVideoFactory;

    /**
     * @method __construct
     * @param  ConfigurationInterface $configuration
     * @param  ResourcesManagement    $cloudinaryResourcesManagement
     * @param  JsonHelper             $jsonHelper
     * @param  ProductVideoFactory    $productVideoFactory
     */
    public function __construct(
        ConfigurationInterface $configuration,
        ResourcesManagement $cloudinaryResourcesManagement,
        JsonHelper $jsonHelper,
        ProductVideoFactory $productVideoFactory
    ) {
        $this->configuration = $configuration;
        $this->cloudinaryResourcesManagement = $cloudinaryResourcesManagement;
        $this->jsonHelper = $jsonHelper;
        $this->productVideoFactory = $productVideoFactory;
    }

    public function execute()
    {
        if ($this->configuration->isEnabled()) {
            $productVideosCollection = $this->productVideoFactory->create()->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('provider', 'cloudinary')
                ->addFieldToFilter('title', ['null' => true])
                ->setOrder('value_id', 'asc')
                ->setPageSize($this->configuration->getScheduledVideoDataImportLimit());
            foreach ($productVideosCollection as $video) {
                $parsedRemoteFileUrl = $this->configuration->parseCloudinaryUrl($video->getUrl());
                $title = $description = $parsedRemoteFileUrl["publicId"];

                $videoData = (array) $this->jsonHelper->jsonDecode($this->cloudinaryResourcesManagement->setId($parsedRemoteFileUrl["publicId"])->getVideo());
                if (!$videoData["error"]) {
                    $videoData["context"] = new DataObject((isset($videoData["data"]["context"])) ? (array)$videoData["data"]["context"] : []);
                    $title = $videoData["context"]->getData('caption') ?: $videoData["context"]->getData('alt');
                    $description = $videoData["context"]->getData('description') ?: $videoData["context"]->getData('alt');
                }
                $title = $title ?: $parsedRemoteFileUrl["publicId"];
                $description = preg_replace('/(&nbsp;|<([^>]+)>)/i', '', $description ?: $title);

                $video->setTitle((string) $title)
                    ->setDescription((string) $description)
                    ->save();
            }
        }
    }
}
