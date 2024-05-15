<?php
namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Autoplay
 * @author Ariel Ashri <arieliens@gmail.com>
 * Date: 14/01/2024
 * Time: 14:22
 */
class Controls implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'all',
                'label' => 'All',
            ],
            [
                'value' => 'play',
                'label' => 'Play buttons',
            ],
            [
                'value' => 'none',
                'label' => 'None',
            ],
        ];
    }
}
