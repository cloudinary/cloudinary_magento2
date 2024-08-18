<?php
namespace Cloudinary\Cloudinary\Plugin;


use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Swatches\Helper\Media as SwatchMediaHelper;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary\Model\Configuration;
class SwatchImageUrlPlugin
{
    public const  SWATCH_MEDIA_PATH = 'attribute/swatch';
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var Configuration .
     */
    protected $_configuration;
    /**
     * @var \Magento\Framework\View\ConfigInterface
     */
    protected $viewConfig;

    /**
     * @var $ImageConfig
     */
    protected $imageConfig;

    protected $imageFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param Configuration $configuration
     * @param \Magento\Framework\View\ConfigInterface $configInterface
     * @param \Magento\Framework\Image\Factory $imageFactory
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        Configuration $configuration,
        \Magento\Framework\View\ConfigInterface $configInterface,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Framework\Filesystem $filesystem,
    ) {
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->_configuration = $configuration;
        $this->viewConfig = $configInterface;
        $this->imageFactory = $imageFactory;
        $this->mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::PUB);
    }

    /**
     * @return array
     */
    public function getImageConfig()
    {
        if (!$this->imageConfig) {
            $this->imageConfig = $this->viewConfig->getViewConfig()->getMediaEntities(
                'Magento_Catalog',
                \Magento\Catalog\Helper\Image::MEDIA_TYPE_CONFIG_NODE
            );
        }

        return $this->imageConfig;
    }

    public function getSwatchCachePath($swatchType)
    {
        return self::SWATCH_MEDIA_PATH . '/' . $swatchType . '/';
    }

    protected function getAbsolutePath($swatchType)
    {
        return $this->mediaDirectory->getAbsolutePath($this->getSwatchCachePath($swatchType));
    }
    /**
     * @param SwatchMediaHelper $subject
     * @param $result
     * @param $swatchType
     * @return mixed|string
     */
    public function afterGetSwatchAttributeImage(SwatchMediaHelper $subject, $result, $swatchType)
    {
        if ($this->_configuration->isEnabled() && $this->_configuration->isLoadSwatchesFromCloudinary()) {
            $swatchTypes = ['swatch_image', 'swatch_thumb'];
            // Check if the file path is valid
            if (in_array($swatchType, $swatchTypes) && strpos($result, '/') !== false) {

                $parsedUrl = parse_url($result, PHP_URL_PATH);
                $absolutePath = $this->mediaDirectory->getAbsolutePath() . $parsedUrl;
                $imageConfig = $this->getImageConfig();

                $image = $this->imageFactory->create($absolutePath);
                $imageId = pathinfo($result, PATHINFO_FILENAME);
                $transformations = [
                    'fetch_format' => $this->_configuration->getFetchFormat(),
                    'quality' =>  $this->_configuration->getImageQuality(),
                    'width' =>  $image->getOriginalWidth() ?? $imageConfig[$swatchType]['width'],
                    'height' => $image->getOriginalHeight() ??  $imageConfig[$swatchType]['height']
                ];
                try {
                    $image = Media::fromParams(
                            $imageId,
                            [
                                'transformation' => $transformations,
                                'secure' => true,
                                'sign_url' => $this->_configuration->getUseSignedUrls(),
                                'version' => 1
                            ]
                        ) . '?_i=AB';


                    return $image;
                } catch (\Exception $e) {
                    throw new LocalizedException($e->getMessage());
                }

            }
        }
        // return result is fallback to default behavior
        return $result;
    }
}
