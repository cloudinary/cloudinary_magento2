<?php
namespace Cloudinary\Cloudinary\Controller\Adminhtml\Ajax;

use Cloudinary\Api\BaseApiClient;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\Image\ImageFactory;
use Cloudinary\Cloudinary\Core\UrlGenerator;
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
        $result = ['error' => 'Invalid configuration'];

        if ($this->configuration->isEnabled()) {
            try {
                $remoteImageUrl = $this->getRequest()->getParam('remote_image');

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

                // Check if this is a Cloudinary rendition path
                if (strpos($relativePath, '.renditions/cloudinary/') !== false) {
                    $parts = explode('.renditions/cloudinary/', $relativePath);
                    $filename = end($parts);

                    // Remove the first cld_ prefix if there are multiple
                    if (preg_match('/^cld_[a-zA-Z0-9]+_/', $filename)) {
                        $filename = preg_replace('/^cld_[a-zA-Z0-9]+_/', '', $filename);
                    }

                    $fileId = 'media/' . $filename;
                } else {
                    $fileId = $relativePath;
                }

                $result = Media::fromParams(
                    $fileId,
                    [
                        'transformation' => $this->transformation->build(),
                        'secure' => true,
                        'sign_url' => $this->configuration->getUseSignedUrls(),
                        'version' => 1
                    ]
                ) . '?_i=AB';

            } catch (\Exception $e) {
                $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
            }
        }

        $response = $this->resultFactory->create();
        $response->setHeader('Content-type', 'application/json');
        $response->setContents(json_encode($result));
        return $response;
    }
}
