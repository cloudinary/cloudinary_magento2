<?php

namespace Cloudinary\Cloudinary\Plugin\Ui\Component\Form\Element\DataType\Media;

/**
 * Plugin for UiComponent Image DataType
 */
class Image
{
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
