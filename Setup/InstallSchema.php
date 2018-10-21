<?php

namespace Cloudinary\Cloudinary\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /**
         * Create table 'cloudinary_synchronisation'
         */
        $table = $setup->getConnection()->newTable(
            $setup->getTable('cloudinary_synchronisation')
        )->addColumn(
            'cloudinary_synchronisation_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned'  => true],
            'Cloudinary Synchronisation ID'
        )->addColumn(
            'image_path',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Image Path'
        );

        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}
