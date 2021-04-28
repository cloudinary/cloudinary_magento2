<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cloudinary\Cloudinary\Block\Adminhtml\Product\Edit;

use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\ProductVideo\Helper\Media;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class NewVideo extends \Magento\ProductVideo\Block\Adminhtml\Product\Edit\NewVideo
{
    /**
     * @var array|null
     */
    protected $_cloudinaryConfig;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var ConfigurationBuilder
     */
    protected $_cloudinaryConfigurationBuilder;

    /**
     * @method __construct
     * @param  Context                $context
     * @param  Registry               $registry
     * @param  FormFactory            $formFactory
     * @param  Media                  $mediaHelper
     * @param  EncoderInterface       $jsonEncoder
     * @param  ConfigurationInterface $configuration
     * @param  ConfigurationBuilder   $cloudinaryConfigurationBuilder
     * @param  array                  $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Media $mediaHelper,
        EncoderInterface $jsonEncoder,
        ConfigurationInterface $configuration,
        ConfigurationBuilder $cloudinaryConfigurationBuilder,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $mediaHelper,
            $jsonEncoder,
            $data
        );
        $this->configuration = $configuration;
        $this->_cloudinaryConfigurationBuilder = $cloudinaryConfigurationBuilder;
    }

    protected function getCloudinaryConfig()
    {
        if (is_null($this->_cloudinaryConfig)) {
            $this->_cloudinaryConfig = $this->_cloudinaryConfigurationBuilder->build();
            if (!$this->_cloudinaryConfig['api_key'] || !$this->_cloudinaryConfig['api_secret'] || !$this->_cloudinaryConfig['cloud_name']) {
                $this->_cloudinaryConfig = false;
            } else {
                $this->_cloudinaryConfig['api_url'] = "https://api.cloudinary.com/v1_1/{$this->_cloudinaryConfig['cloud_name']}/";
            }
        }

        return $this->_cloudinaryConfig;
    }

    /**
     * Get widget options
     *
     * @return string
     */
    public function getWidgetOptions()
    {
        return $this->jsonEncoder->encode(
            [
                'saveVideoUrl' => $this->getUrl('catalog/product_gallery/upload'),
                'saveRemoteVideoUrl' => $this->getUrl('product_video/product_gallery/retrieveImage'),
                'htmlId' => $this->getHtmlId(),
                'youTubeApiKey' => $this->mediaHelper->getYouTubeApiKey(),
                'videoSelector' => $this->videoSelector,
                'cloudinaryPlaceholder' => $this->getPlaceholderUrl(),
            ]
        );
    }

    /**
     * Get note for video url
     *
     * @return \Magento\Framework\Phrase
     */
    protected function getNoteVideoUrl()
    {
        $result = __('Supported: Vimeo');
        $messages = "";
        if ($this->mediaHelper->getYouTubeApiKey() === null) {
            $messages .= __('<br>*To add YouTube video, please <a href="%1">enter YouTube API Key</a> first.', $this->getConfigApiKeyUrl());
        } else {
            $result .= __(', YouTube');
        }

        if (!$this->getCloudinaryConfig()) {
            $messages .= __('<br>*To add Cloudinary video, please <a href="%1">enter your Cloudinary Account Credentials</a> first.', $this->getCloudinaryConfigUrl());
        } else {
            $result .= __(', Cloudinary');
        }

        return $result . $messages;
    }

    /**
     * Get url for Cloudinary config params
     *
     * @return string
     */
    protected function getCloudinaryConfigUrl()
    {
        return $this->urlBuilder->getUrl(
            'adminhtml/system_config/edit',
            [
                'section' => 'cloudinary'
            ]
        );
    }

    /**
     * @return string
     */
    protected function getPlaceholderUrl()
    {
        $storeManager = $this->configuration->getStoreManager();
        $configPaths = [
            'catalog/placeholder/image_placeholder',
            'catalog/placeholder/small_image_placeholder',
            'catalog/placeholder/thumbnail_placeholder',
        ];
        foreach ($configPaths as $configPath) {
            if (($path = $storeManager->getStore()->getConfig($configPath))) {
                return $storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product/placeholder/' . $path;
                break;
            }
        }
        return $this->getViewFileUrl('Cloudinary_Cloudinary::images/cloudinary_cloud_glyph_blue.png');
    }
}
