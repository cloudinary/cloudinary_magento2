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

        if (version_compare($context->getVersion(), '1.12.0', '<')) {
            $this->createProductGalleryApiQueueTable($setup);
        }

        if (version_compare($context->getVersion(), '1.13.0', '<')) {
            $this->createProductSpinsetMapTable($setup);
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

    /**
     *
     * @param SchemaSetupInterface $setup
     */
    private function createProductGalleryApiQueueTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('cloudinary_product_gallery_api_queue')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'sku',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Product SKU'
        )->addColumn(
            'full_item_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            3000,
            ['nullable' => true],
            'Prepared Schema'
        )
        ->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )
        ->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Created At'
        )
        ->addColumn(
            'success',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            1,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Success'
        )
        ->addColumn(
            'success_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['nullable' => true],
            'Success At'
        )
        ->addColumn(
            'message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            3000,
            ['nullable' => true],
            'Message'
        )
        ->addColumn(
            'has_errors',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            1,
            ['unsigned' => true, 'nullable' => true, 'default' => '0'],
            'Has Errors'
        )
        ->addColumn(
            'tryouts',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            ['unsigned' => true, 'nullable' => true, 'default' => '0'],
            'Tryouts'
        );
        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function createProductSpinsetMapTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('cloudinary_product_spinset_map')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Store ID'
        )->addColumn(
            'image_name',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Relative image path'
        )->addColumn(
            'cldspinset',
            Table::TYPE_TEXT,
            255,
            [],
            'Cloudinary Spinset Tag'
        )->addIndex(
            $setup->getIdxName(
                'cloudinary_product_spinset_map',
                ['store_id', 'image_name'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['store_id', 'image_name'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        );

        $setup->getConnection()->createTable($table);
    }
}
