<?php
namespace Cloudinary\Cloudinary\Plugin;


use Magento\Framework\Exception\LocalizedException;
use Magento\Swatches\Helper\Media as SwatchMediaHelper;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary\Model\Configuration;
class SwatchImageUrlPlugin
{
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

    /**
     * Constructor
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param Configuration $configuration
     * @param \Magento\Framework\View\ConfigInterface $configInterface
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        Configuration $configuration,
        \Magento\Framework\View\ConfigInterface $configInterface,
    ) {
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->_configuration = $configuration;
        $this->viewConfig = $configInterface;
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

                $imageConfig = $this->getImageConfig();

                $imageId = pathinfo($result, PATHINFO_FILENAME);
                $transformations = [
                    'fetch_format' => $this->_configuration->getFetchFormat(),
                    'quality' =>  $this->_configuration->getImageQuality(),
                    'width' => $imageConfig[$swatchType]['width'] ?? null,
                    'height' =>  $imageConfig[$swatchType]['height'] ?? null
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
