<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog product form gallery content
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 *
 * @method \Magento\Framework\Data\Form\Element\AbstractElement getElement()
 */
namespace Cloudinary\Cloudinary\Block\Adminhtml\Product\Helper\Form\Gallery;

use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\UrlInterface;

/**
 * Block for gallery content.
 */
class Content extends \Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery\Content
{
    /**
     * @var string
     */
    protected $_template = 'Cloudinary_Cloudinary::catalog/product/helper/gallery.phtml';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * @var ConfigurationBuilder
     */
    protected $configurationBuilder;

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
     * @param EncoderInterface $jsonEncoder
     * @param Config $mediaConfig
     * @param ConfigurationInterface $configuration
     * @param ConfigurationBuilder $configurationBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        Config $mediaConfig,
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $mediaConfig, $data);
        $this->urlBuilder = $context->getUrlBuilder();
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
    }

    /**
     * Get Cloudinary media library widget options
     *
     * @return string
     */
    public function getCloudinaryMediaLibraryWidgetOptions($refresh = false)
    {
        if ((is_null($this->cloudinaryMLoptions) || $refresh) && $this->configuration->isEnabled()) {
            $this->cloudinaryMLoptions = [];
            $this->timestamp = time();
            $this->credentials = $this->configurationBuilder->build();
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
        if (!$this->cloudinaryMLoptions) {
            return null;
        }
        return $this->_jsonEncoder->encode(
            [
                'htmlId' => $this->getHtmlId(),
                'cloudinaryPlaceholder' => $this->getViewFileUrl('Cloudinary_Cloudinary::images/cloudinary_vertical_logo_for_white_bg.svg'),
                'uploaderUrl' => $this->_urlBuilder->addSessionParam()->getUrl('cloudinary/product_gallery/upload'),
                'cloudinaryMLoptions' => $this->cloudinaryMLoptions,
            ]
        );
    }
}
