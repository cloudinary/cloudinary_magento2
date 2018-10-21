<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown;

use Magento\Framework\Data\OptionSourceInterface;

class Quality implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '20',
                'label' => '20%',
            ),
            array(
                'value' => '30',
                'label' => '30%',
            ),
            array(
                'value' => '40',
                'label' => '40%',
            ),
            array(
                'value' => '50',
                'label' => '50%',
            ),
            array(
                'value' => '60',
                'label' => '60%',
            ),
            array(
                'value' => '70',
                'label' => '70%',
            ),
            array(
                'value' => '80',
                'label' => '80%',
            ),
            array(
                'value' => '90',
                'label' => '90%',
            ),
            array(
                'value' => '100',
                'label' => '100%',
            ),
            array(
                'value' => 'auto',
                'label' => 'Auto',
            )
        );
    }
}
