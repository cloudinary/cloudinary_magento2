<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cloudinary\Cloudinary\Block\Adminhtml\Product\Edit;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class NewVideo extends \Magento\ProductVideo\Block\Adminhtml\Product\Edit\NewVideo
{
    protected $_cloudinaryConfig;

    /**
     * @var \Cloudinary\Cloudinary\Core\ConfigurationBuilder
     */
    protected $_cloudinaryConfigurationBuilder;

    /**
     * @param \Magento\Backend\Block\Template\Context          $context
     * @param \Magento\Framework\Registry                      $registry
     * @param \Magento\Framework\Data\FormFactory              $formFactory
     * @param \Magento\ProductVideo\Helper\Media               $mediaHelper
     * @param \Magento\Framework\Json\EncoderInterface         $jsonEncoder
     * @param \Cloudinary\Cloudinary\Core\ConfigurationBuilder $cloudinaryConfigurationBuilder
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\ProductVideo\Helper\Media $mediaHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Cloudinary\Cloudinary\Core\ConfigurationBuilder $cloudinaryConfigurationBuilder,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $mediaHelper, $jsonEncoder, $data);
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
                'cloudinaryPlaceholder' => $this->getViewFileUrl('Cloudinary_Cloudinary::images/cloudinary_vertical_logo_for_white_bg.svg')
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
}
