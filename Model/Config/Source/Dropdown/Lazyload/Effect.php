<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Lazyload;

use Magento\Framework\Data\OptionSourceInterface;

class Effect implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'show',
                'label' => 'Show',
            ],
            [
                'value' => 'fadeIn',
                'label' => 'Fade In',
            ],
        ];
    }
}
