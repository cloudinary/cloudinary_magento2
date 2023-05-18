<?php

namespace Cloudinary\Cloudinary\Block;

use Cloudinary\Cloudinary\Helper\MediaLibraryHelper;
use Magento\Framework\View\Element\Template;

class Scripts extends Template
{

    protected $_helper;

    public function __construct(
        Template\Context $context,
        MediaLibraryHelper $mediaHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_helper = $mediaHelper;
    }

    /**
     * @return void
     */
    public function getCname()
    {
        return $this->_helper->getCname();
    }


}
