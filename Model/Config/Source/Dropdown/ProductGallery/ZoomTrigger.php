<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\ProductGallery;

use Magento\Framework\Data\OptionSourceInterface;

class ZoomTrigger implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'click',
                'label' => 'Click',
            ],
            [
                'value' => 'hover',
                'label' => 'Hover',
            ],
        ];
    }
}
