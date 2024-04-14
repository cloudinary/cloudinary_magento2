<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown\Video\ABR;

use Magento\Framework\UrlInterface;
use Magento\Config\Model\Config\CommentInterface;


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

        if ($elementValue == 'optimization') {
            $comment = '';
        }
        return $comment;
    }
}
