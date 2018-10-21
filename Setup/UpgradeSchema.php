<?php

namespace Cloudinary\Cloudinary\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.5.0', '<=')) {
            $this->createTransformationTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function createTransformationTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('cloudinary_transformation')
        )->addColumn(
            'image_name',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'primary' => true],
            'Relative image path'
        )->addColumn(
            'free_transformation',
            Table::TYPE_TEXT,
            255,
            [],
            'Free transformation'
        );

        $setup->getConnection()->createTable($table);
    }
}
