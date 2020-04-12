<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Lazyload;

use Magento\Framework\Data\OptionSourceInterface;

class Placeholder implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'blur',
                'label' => 'Blur',
            ],
            [
                'value' => 'pixelate',
                'label' => 'Pixelate',
            ],
            [
                'value' => 'predominant-color',
                'label' => 'Predominant color',
            ],
            [
                'value' => 'vectorize',
                'label' => 'Vectorize',
            ],
        ];

        /*
        export const placeholderImageOptions = {
          'vectorize': {effect: 'vectorize', quality: 1},
          'pixelate': {effect: 'pixelate', quality: 1, fetch_format: 'auto'},
          'blur': {effect: 'blur:2000', quality: 1, fetch_format: 'auto'},
          'predominant-color': predominantColorTransform
        };
         */
    }
}
