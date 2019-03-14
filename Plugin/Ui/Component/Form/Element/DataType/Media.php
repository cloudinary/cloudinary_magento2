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
            $component->setData(array_replace_recursive(
                $component->getData(),
                [
                    'config' => [
                        'template' => 'Cloudinary_Cloudinary/form/element/uploader/uploader',
                        'component' => 'Cloudinary_Cloudinary/js/form/element/file-uploader',
                        'cloudinaryMLoptions' => [
                            'imageUploaderUrl' => $component->getContext()->getUrl('cloudinary/ajax/retrieveImage', ['_secure' => true]),
                            'addTmpExtension' => true,
                            'cloudinaryMLoptions' => $cloudinaryMLoptions,
                            'cloudinaryMLshowOptions' => $this->mediaLibraryHelper->getCloudinaryMLshowOptions("image"),
                        ]
                    ]
                ]
            ));
        }
        return $result;
    }
}
