<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown;

use Magento\Framework\Data\OptionSourceInterface;

class Dpr implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '1.0',
                'label' => '1.0',
            ),
            array(
                'value' => '2.0',
                'label' => '2.0',
            ),
        );
    }
}
