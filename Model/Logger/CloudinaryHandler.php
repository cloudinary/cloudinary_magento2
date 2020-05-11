<?php
namespace Cloudinary\Cloudinary\Model\Logger;

use Monolog\Logger;

class CloudinaryHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/cloudinary_cloudinary.log';
}
