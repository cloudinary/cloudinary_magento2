<?php

namespace Cloudinary\Cloudinary\Plugin\Ui\Component\Form\Element\DataType;

use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;

/**
 * Plugin for UiComponent Media DataType
 */
class Media
{
    /**
     * MediaLibraryHelper
     * @var array|null
     */
    protected $mediaLibraryHelper;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @param MediaLibraryHelper $mediaLibraryHelper
     * @param State $appState
     */
    public function __construct(
        MediaLibraryHelper $mediaLibraryHelper,
        State $appState
    ) {
        $this->mediaLibraryHelper = $mediaLibraryHelper;
        $this->appState = $appState;
    }

    /**
     * Prepare component configuration
     *
     * @param \Magento\Ui\Component\Form\Element\DataType\Media $component
     * @param mixed $result
     * @return void
     */
    public function afterPrepare(\Magento\Ui\Component\Form\Element\DataType\Media $component, $result = null)
    {
        if ($this->appState->getAreaCode() === Area::AREA_ADMINHTML && ($cloudinaryMLoptions = $this->mediaLibraryHelper->getCloudinaryMLOptions(false))) {
            $uploaderConfigUrl = $component->getData('config/uploaderConfig/url');
            if (strpos($uploaderConfigUrl, '/design_config_fileUploader/') !== false) {
                $type = 'design_config_fileUploader';
            } elseif (strpos($uploaderConfigUrl, '/category_image/') !== false) {
                $type = 'category_image';
            } elseif (strpos($uploaderConfigUrl, '/pagebuilder/contenttype/') !== false) {
                $type = 'pagebuilder_contenttype';
            } else {
                $type = null;
            }
            if ($type) {
                $component->setData(array_replace_recursive(
                    $component->getData(),
                    [
                        'config' => [
                            'template' => 'Cloudinary_Cloudinary/form/element/uploader/uploader',
                            'component' => 'Cloudinary_Cloudinary/js/form/element/file-uploader',
                            'cloudinaryMLoptions' => [
                                'imageUploaderUrl' => $component->getContext()->getUrl('cloudinary/ajax/retrieveImage', ['_secure' => true, 'type' => $type]),
                                'addTmpExtension' => false,
                                'cloudinaryMLoptions' => $cloudinaryMLoptions,
                                'cloudinaryMLshowOptions' => $this->mediaLibraryHelper->getCloudinaryMLshowOptions("image"),
                            ]
                        ]
                    ]
                ));
            }
        }
        return $result;
    }
}
