<?php
namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class StreamProtocol
 * @package Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video
 * @author Ariel Ashri <arieliens@gmail.com>
 * Date: 10/03/2024
 * Time: 17:54
 */
class StreamProtocol implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'dash',
                'label' => 'Dynamic adaptive streaming over HTTP (MPEG-DASH)',
            ],
            [
                'value' => 'hls',
                'label' => 'HTTP live streaming (HLS)',
            ],
        ];
    }
}
