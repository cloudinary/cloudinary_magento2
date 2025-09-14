<?php
namespace Cloudinary\Cloudinary\Controller\Adminhtml\Ajax;

use Cloudinary\Api\BaseApiClient;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\Image\ImageFactory;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Configuration\Configuration;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\RawFactory as ResultRawFactory;
use Magento\Backend\App\Action\Context;
use Magento\Cms\Model\Wysiwyg\Images\GetInsertImageContent;
use Magento\Framework\Filesystem as FileSysten;
use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary\Core\Image\Transformation;

class UpdateAdminImage extends Action
{
    /**
     * @var ConfigurationInterface
     */
    protected $configuration;
    /**
     * @var UrlGenerator
     */
    protected $urlGenerator;
    /**
     * @var ImageFactory
     */
    protected $imageFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    protected $resultFactory;

    protected $imageContent;

    protected $filesystem;

    private $_authorised;

    protected $configurationBuilder;

    protected $transformation;

    /**
     * @param ImageFactory $imageFactory
     * @param UrlGenerator $urlGenerator
     * @param ConfigurationInterface $configuration
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        Context $context,
        ImageFactory $imageFactory,
        UrlGenerator $urlGenerator,
        ConfigurationInterface $configuration,
        StoreManagerInterface $storeManager,
        UrlInterface $urlInterface,
        ResultRawFactory $resultFactory,
        FileSysten $filesystem,
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
        $this->filesystem = $filesystem;
        $this->configurationBuilder = $configurationBuilder;
        $this->transformation = $transformation;
    }

    private function authorise()
    {
        if (!$this->_authorised && $this->configuration->isEnabled()) {
            Configuration::instance($this->configurationBuilder->build());
            BaseApiClient::$userPlatform =  $this->configuration->getUserPlatform();
            $this->_authorised = true;
        }
    }

    public function execute()
    {
        $this->authorise();
        $result = [];
        if ($this->configuration->isEnabled()) {
            try{
                $remoteImageUrl = $this->getRequest()->getParam('remote_image');
                $parsedUrl = parse_url($remoteImageUrl);
                $cleanUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
                $baseUrl = $this->storeManager->getStore()->getBaseUrl();
                $relativePath = str_replace($baseUrl, '', $cleanUrl);

                // file id can be media/wmpvownqus8xwvylswsr_1

                // Check if this is a Cloudinary rendition path
                if (strpos($relativePath, '.renditions/cloudinary/') !== false) {
                    // Extract the filename from the renditions path
                    $parts = explode('.renditions/cloudinary/', $relativePath);
                    $filename = end($parts);

                    // Remove the first cld_ prefix if there are multiple
                    // e.g., cld_68b6aa57b4d59_cld_6458c4355ee79_cld-sample-2.jpg -> cld_6458c4355ee79_cld-sample-2.jpg
                    if (preg_match('/^cld_[a-z0-9]+_/', $filename)) {
                        $filename = preg_replace('/^cld_[a-z0-9]+_/', '', $filename);
                    }

                    // The public ID should be media/filename for Cloudinary
                    $filedId = 'media/' . $filename;
                } else {
                    // For regular media files, use the relative path as is
                    $filedId = $relativePath;

                    // Remove media/ prefix if present (it will be in the public ID already)
                    if (strpos($filedId, 'media/') === 0) {
                        $filedId = substr($filedId, 6);
                    }
                }

                $result =  Media::fromParams(
                        $filedId,
                        [   'transformation' => $this->transformation->build(),
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
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));
        return $response;
    }
}
