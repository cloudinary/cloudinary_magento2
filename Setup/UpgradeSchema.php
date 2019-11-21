<?php

namespace Cloudinary\Cloudinary\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.5.0', '<')) {
            $this->createTransformationTable($setup);
        }

        if (version_compare($context->getVersion(), '1.10.3', '<')) {
            $this->createMediaLibraryMapTable($setup);
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

    /**
     * @param SchemaSetupInterface $setup
     */
    private function createMediaLibraryMapTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('cloudinary_media_library_map')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'cld_uniqid',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Relative image path'
        )->addColumn(
            'cld_public_id',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Cloudinary Public ID'
        )->addColumn(
            'free_transformation',
            Table::TYPE_TEXT,
            255,
            [],
            'Free transformation'
        )->addIndex(
            $setup->getIdxName(
                'cloudinary_media_library_map',
                ['cld_uniqid'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['cld_uniqid'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        );

        $setup->getConnection()->createTable($table);
    }
}
