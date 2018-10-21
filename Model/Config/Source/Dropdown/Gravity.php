<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown;

use Magento\Framework\Data\OptionSourceInterface;

class Gravity implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '',
                'label' => 'Magento\'s Default',
            ),
            array(
                'value' => 'face',
                'label' => 'Face',
            ),
            array(
                'value' => 'faces',
                'label' => 'Faces',
            ),
            array(
                'value' => 'north_west',
                'label' => 'North West',
            ),
            array(
                'value' => 'north',
                'label' => 'North',
            ),
            array(
                'value' => 'north_east',
                'label' => 'North East',
            ),
            array(
                'value' => 'east',
                'label' => 'East',
            ),
            array(
                'value' => 'center',
                'label' => 'Center',
            ),
            array(
                'value' => 'west',
                'label' => 'West',
            ),
            array(
                'value' => 'south_west',
                'label' => 'South West',
            ),
            array(
                'value' => 'south',
                'label' => 'South',
            ),
            array(
                'value' => 'south_east',
                'label' => 'South East',
            ),
            array(
                'value' => 'face:center',
                'label' => 'Face (Center)',
            ),
            array(
                'value' => 'faces:center',
                'label' => 'Faces (Center)',
            ),
        );
    }
}
