<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video\Autoplay;

use Magento\Framework\UrlInterface;
use Magento\Config\Model\Config\CommentInterface;

/**
 * Class Comment
 * @package Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video\Autoplay
 * @author Ariel Ashri <arieliens@gmail.com>
 * Date: 07/03/2024
 * Time: 14:04
 */
class Comment implements CommentInterface
{
    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        $url = 'https://cloudinary.com/glossary/video-autoplay';

        $comment = '';

        return $comment;
    }
}
