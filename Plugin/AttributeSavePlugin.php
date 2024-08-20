<?php
namespace Cloudinary\Cloudinary\Plugin;

use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Cloudinary\Model\Configuration;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as AttributeResource;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Swatches\Model\ResourceModel\Swatch\CollectionFactory as SwatchCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Swatches\Model\Swatch;
class AttributeSavePlugin
{
    const SWATCH_MEDIA_PATH = 'attribute/swatch';
    /**
     * @var Configuration
     */
    protected $_configuration;
    /**
     * @var CloudinaryImageManager
     */
    protected $_cldImageManager;
    /**
     * @var SwatchCollectionFactory
     */
    protected $_swatchFactory;
    /**
     * @var DirectoryList
     */

    protected $directoryList;


    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @param Configuration $configuration
     * @param CloudinaryImageManager $cldImageManager
     * @param SwatchCollectionFactory $swatchFactory
     * @param ResourceConnection $resource
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Configuration $configuration,
        CloudinaryImageManager $cldImageManager,
        SwatchCollectionFactory $swatchFactory,
        ResourceConnection $resource,
        DirectoryList $directoryList

    )
    {
        $this->_configuration = $configuration;
        $this->_cldImageManager = $cldImageManager;
        $this->_swatchFactory = $swatchFactory;
        $this->resource = $resource;
        $this->directoryList = $directoryList;

    }


    /**
     * @param $filepath
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getMediaPath($filepath)
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . self::SWATCH_MEDIA_PATH . $filepath;
    }




    /**
     * After save plugin to update swatch image URLs
     *
     * @param AttributeResource $subject
     * @param Attribute $attribute
     * @return Attribute
     */
    public function afterSave(AttributeResource $subject, Attribute $attribute)
    {
        if ($this->_configuration->isEnabled() && $this->_configuration->isLoadSwatchesFromCloudinary()) {

            if ($attribute->getFrontendInput() === 'select' && $attribute->getData('swatch_input_type') === 'visual') {
                $connection = $this->resource->getConnection();
                $optionTable = $connection->getTableName('eav_attribute_option');

                // Get all option IDs for the current attribute
                $optionIds = $connection->fetchCol(
                    $connection->select()
                        ->from($optionTable, 'option_id')
                        ->where('attribute_id = ?', $attribute->getId())
                );

                if (!empty($optionIds)) {
                    $swatchCollection = $this->_swatchFactory->create()
                        ->addFieldToFilter('option_id', ['in' => $optionIds])
                        ->addFieldToFilter('type', ['eq' => Swatch::SWATCH_TYPE_VISUAL_IMAGE]);

                    foreach ($swatchCollection as $swatch) {
                        if ( $swatch->getValue()) {
                            // Visual Image means
                            $imagePath = $this->getMediaPath($swatch->getValue());
                            $image = $this->_configuration->getMediaBaseUrl() . self::SWATCH_MEDIA_PATH . $swatch->getValue();
                            try {
                                $cldImage = $this->_cldImageManager->uploadAndSynchronise(
                                    Image::fromPath($imagePath)
                                );
                            } catch (\Exception $e) {
                                throw new LocalizedException($e->getMessage());
                            }

                            //$swatch->save()
                        }
                    }
                }
            }
        }

        return $attribute;
    }
}
