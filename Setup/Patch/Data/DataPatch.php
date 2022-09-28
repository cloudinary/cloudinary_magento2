<?php

namespace Cloudinary\Cloudinary\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Upgrade Data script (Patch)
 *
 * @codeCoverageIgnore
 */
class DataPatch implements DataPatchInterface, PatchRevertableInterface, PatchVersionInterface
{
    /**
     * @param ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param ConsoleOutput
     */
    protected $output;

    /*
    * @var ModuleDataSetupInterface
    */
    private $moduleDataSetup;

    /**
     * constructor init
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ResourceConnection $resourceConnection
     * @param ConsoleOutput $output
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceConnection $resourceConnection,
        ConsoleOutput $output
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resourceConnection = $resourceConnection;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        if (version_compare($this->getVersion(), '1.6.3') < 0) {
            if ($this->getVersion()) {
                $this->output->writeln("<comment>Reseting configurations for 'website' & 'store' scopes (only supports 'default' at the moment)</comment>");
            }

            $this->resourceConnection->getConnection()->delete(
                $this->resourceConnection->getTableName('core_config_data'),
                "path LIKE 'cloudinary/%' AND scope != 'default'"
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        //Here should go code that will revert all operations from `apply` method
        //Please note, that some operations, like removing data from column, that is in role of foreign key reference
        //is dangerous, because it can trigger ON DELETE statement
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies()
    {
        /**
         * This is dependency to another patch. Dependency should be applied first
         * One patch can have few dependencies
         * Patches do not have versions, so if in old approach with Install/Ugrade data scripts you used
         * versions, right now you need to point from patch with higher version to patch with lower version
         * But please, note, that some of your patches can be independent and can be installed in any sequence
         * So use dependencies only if this important for you
         */
        return [];
    }

    public function getAliases()
    {
        /**
         * This internal Magento method, that means that some patches with time can change their names,
         * but changing name should not affect installation process, that's why if we will change name of the patch
         * we will add alias here
         */
        return [];
    }

    public static function getVersion()
    {
        return '1.18.0';
    }
}
