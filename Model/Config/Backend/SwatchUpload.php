<?php
namespace Cloudinary\Cloudinary\Model\Config\Backend;


use Cloudinary\Cloudinary\Core\Image;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\ValidatorException;
use Magento\Swatches\Model\ResourceModel\Swatch\CollectionFactory as SwatchCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Swatches\Model\Swatch;
use Cloudinary\Cloudinary\Model\Configuration;
use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

class SwatchUpload extends Value
{
    const ERROR_DEFAULT = 'Error uploading swatches to Cloudinary Media Library.';
    const SWATCH_MEDIA_PATH = 'attribute/swatch';

    /**
     * @var SwatchCollectionFactory
     */
    protected $swatchCollectionFactory;

    protected $_configuration;

    protected $_cldImageManager;

    /**
     * @var DirectoryList
     */
    protected $directoryList;


    /**
     * @param SwatchCollectionFactory $swatchCollectionFactory
     * @param DirectoryList $directoryList
     * @param Configuration $configuration
     * @param CloudinaryImageManager $cldImageManager
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        SwatchCollectionFactory $swatchCollectionFactory,
        DirectoryList $directoryList,
        Configuration $configuration,
        CloudinaryImageManager $cldImageManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->swatchCollectionFactory = $swatchCollectionFactory;
        $this->directoryList = $directoryList;
        $this->_configuration = $configuration;
        $this->_cldImageManager = $cldImageManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }


    public function getMediaPath($filepath)
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . self::SWATCH_MEDIA_PATH . $filepath;
    }
    /**
     * After saving the configuration, upload swatch images to the CDN
     *
     * @return $this
     * @throws LocalizedException
     */
    public function afterSave()
    {
        $originalValue = $this->getOldValue();
        // Check if the CDN setting is enabled
        if ($this->getvalue() && $this->getValue() != $originalValue) {
            // Get all swatches of type 2 (visual swatches)
            $swatchCollection = $this->swatchCollectionFactory->create()
                ->addFieldToFilter('type', ['eq' => Swatch::SWATCH_TYPE_VISUAL_IMAGE]);

            foreach ($swatchCollection as $swatch) {
                $file = $swatch->getData('value');
                $imagePath = $this->getMediaPath($file);
                $image = $this->_configuration->getMediaBaseUrl() . self::SWATCH_MEDIA_PATH . $swatch->getValue();
                try {
                    $cldImage = $this->_cldImageManager->uploadAndSynchronise(
                        Image::fromPath($imagePath)
                    );
                } catch (\Exception $e) {
                    throw new ValidatorException(self::ERROR_DEFAULT);
                }
            }
        }

        return parent::afterSave();
    }

}
