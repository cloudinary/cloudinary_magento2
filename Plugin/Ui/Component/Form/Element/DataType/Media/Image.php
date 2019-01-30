<?php

namespace Cloudinary\Cloudinary\Plugin\Ui\Component\Form\Element\DataType\Media;

use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Framework\App\State;

/**
 * Plugin for UiComponent Image DataType
 */
class Image
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
     * @param \Magento\Ui\Component\Form\Element\DataType\Media\Image $component
     * @param mixed $result
     * @return void
     */
    public function afterPrepare(\Magento\Ui\Component\Form\Element\DataType\Media\Image $component, $result = null)
    {
        if ($component->getData('config/cloudinaryMLoptions')) {
            $component->setData(array_replace_recursive(
                $component->getData(),
                [
                    'config' => [
                        'template' => 'Cloudinary_Cloudinary/form/element/uploader/image',
                        'component' => 'Cloudinary_Cloudinary/js/form/element/image-uploader',
                    ]
                ]
            ));
        }
        return $result;
    }
}
