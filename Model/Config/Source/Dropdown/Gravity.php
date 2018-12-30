<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown;

use Magento\Framework\Data\OptionSourceInterface;

class Gravity implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => '',
                'label' => 'Magento\'s Default',
            ],
            [
                'value' => 'north_west',
                'label' => 'North West',
            ],
            [
                'value' => 'north',
                'label' => 'North',
            ],
            [
                'value' => 'north_east',
                'label' => 'North East',
            ],
            [
                'value' => 'east',
                'label' => 'East',
            ],
            [
                'value' => 'center',
                'label' => 'Center',
            ],
            [
                'value' => 'west',
                'label' => 'West',
            ],
            [
                'value' => 'south_west',
                'label' => 'South West',
            ],
            [
                'value' => 'south',
                'label' => 'South',
            ],
            [
                'value' => 'south_east',
                'label' => 'South East',
            ],
        ];
    }
}
