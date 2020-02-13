<?php

namespace Cloudinary\Cloudinary\Model\Config\Source\Dropdown;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CmsBlocks implements OptionSourceInterface
{
    /**
     * @var BlockFactory
     */
    private $blockFactory;

    public function __construct(
        BlockFactory $blockFactory
    ) {
        $this->blockFactory = $blockFactory;
    }

    public function toOptionArray()
    {
        $options = [];
        foreach ($this->blockFactory->create()->getCollection()->setOrder('title', 'asc') as $block) {
            $options[] = [
                'value' => $block->getIdentifier(),
                'label' => $block->getTitle(),
            ];
        }
        return $options;
    }
}
