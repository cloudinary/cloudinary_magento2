<?php

namespace Cloudinary\Cloudinary\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Upgrade Data script
 *
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @param ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param ConsoleOutput
     */
    protected $output;

    /**
     * Init
     *
     * @method __construct
     * @param  ResourceConnection $resourceConnection
     * @param  ConsoleOutput      $output
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ConsoleOutput $output
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.6.3') < 0) {
            if ($context->getVersion()) {
                $this->output->writeln("<comment>Reseting configurations for 'website' & 'store' scopes (only supports 'default' at the moment)</comment>");
            }

            $this->resourceConnection->getConnection()->delete(
                $this->resourceConnection->getTableName('core_config_data'),
                "path LIKE 'cloudinary/%' AND scope != 'default'"
            );
        }

        $setup->endSetup();
    }
}
