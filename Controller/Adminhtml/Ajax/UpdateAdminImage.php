<?php
namespace Cloudinary\Cloudinary\Controller\Adminhtml\Ajax;

use Cloudinary\Api\BaseApiClient;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\Image\ImageFactory;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Configuration\Configuration;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\RawFactory as ResultRawFactory;
use Magento\Backend\App\Action\Context;
use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary\Core\Image\Transformation;

class UpdateAdminImage extends Action
{
    const ADMIN_RESOURCE = 'Cloudinary_Cloudinary::config';

    protected $configuration;
    protected $urlGenerator;
    protected $imageFactory;
    protected $storeManager;
    protected $urlInterface;
    protected $resultFactory;
    protected $configurationBuilder;
    protected $transformation;
    private $_authorised;


    /**
     * @param Context $context
     * @param ImageFactory $imageFactory
     * @param UrlGenerator $urlGenerator
     * @param ConfigurationInterface $configuration
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlInterface
     * @param ResultRawFactory $resultFactory
     * @param ConfigurationBuilder $configurationBuilder
     * @param Transformation $transformation
     */
    public function __construct(
        Context $context,
        ImageFactory $imageFactory,
        UrlGenerator $urlGenerator,
        ConfigurationInterface $configuration,
        StoreManagerInterface $storeManager,
        UrlInterface $urlInterface,
        ResultRawFactory $resultFactory,
        ConfigurationBuilder $configurationBuilder,
        Transformation $transformation
    ) {
        parent::__construct($context);
        $this->imageFactory = $imageFactory;
        $this->urlGenerator = $urlGenerator;
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
        $this->urlInterface = $urlInterface;
        $this->resultFactory = $resultFactory;
        $this->configurationBuilder = $configurationBuilder;
        $this->transformation = $transformation;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    private function authorise()
    {
        if (!$this->_authorised && $this->configuration->isEnabled()) {
            Configuration::instance($this->configurationBuilder->build());
            BaseApiClient::$userPlatform = $this->configuration->getUserPlatform();
            $this->_authorised = true;
        }
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->authorise();
        $remoteImageUrl = $this->getRequest()->getParam('remote_image');
        $result = $remoteImageUrl ?: ['error' => 'Invalid configuration'];

        if ($this->configuration->isEnabled()) {
            try {
                // Validate URL
                if (!$remoteImageUrl) {
                    throw new \InvalidArgumentException('Missing remote_image parameter');
                }

                $parsedUrl = parse_url($remoteImageUrl);

                if (!$parsedUrl || !isset($parsedUrl['scheme'], $parsedUrl['host'], $parsedUrl['path'])) {
                    throw new \InvalidArgumentException('Invalid image URL');
                }

                $cleanUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
                $baseUrl = $this->storeManager->getStore()->getBaseUrl();
                $relativePath = str_replace($baseUrl, '', $cleanUrl);
                $relativePath = ltrim($relativePath, '/');

                // Create Image object and use UrlGenerator (same as storefront)
                $image = Image::fromPath($remoteImageUrl, $relativePath);

                // Use UrlGenerator which handles all the logic including database mapping
                $cloudinaryUrl = $this->urlGenerator->generateFor($image, $this->transformation);
                $result = (string)$cloudinaryUrl;

            } catch (\Exception $e) {
                // Return original URL on error for graceful fallback
                error_log(sprintf(
                    'Cloudinary UpdateAdminImage error: %s for image: %s',
                    $e->getMessage(),
                    $remoteImageUrl
                ));
            }
        }

        $response = $this->resultFactory->create();
        $response->setHeader('Content-type', 'application/json');
        $response->setContents(json_encode($result));
        return $response;
    }
}
