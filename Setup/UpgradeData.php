<?php

namespace Cloudinary\Cloudinary\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Upgrade Data script
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * @param ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * Init
     * @method __construct
     * @param  EavSetupFactory    $eavSetupFactory
     * @param  ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->_resourceConnection = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.6.3') < 0) {
            echo "- Reseting configurations for 'website' & 'store' scopes (only supports 'default' at the moment).\n";
            $this->_resourceConnection->getConnection()->delete(
                $this->_resourceConnection->getTableName('core_config_data'),
                "path LIKE 'cloudinary/%' AND scope != 'default'"
            );
        }

        $setup->endSetup();
    }
}
