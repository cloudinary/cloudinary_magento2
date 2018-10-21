<?php

namespace Cloudinary\Cloudinary\Helper;

use Magento\Framework\App\ResourceConnection;
use Cloudinary\Cloudinary\Model\ResourceModel\Synchronisation;
use Cloudinary\Cloudinary\Model\ResourceModel\Transformation;

class Reset
{
    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @var Synchronisation
     */
    private $synchronisation;

    /**
     * @var Transformation
     */
    private $transformation;

    /**
     * @param ResourceConnection $connection
     * @param Synchronisation $synchronisation
     * @param Transformation $transformation
     */
    public function __construct(
        ResourceConnection $connection,
        Synchronisation $synchronisation,
        Transformation $transformation
    ) {
        $this->connection = $connection;
        $this->synchronisation = $synchronisation;
        $this->transformation = $transformation;
    }

    public function resetModule()
    {
        $this->truncate($this->synchronisationTableName());
        $this->truncate($this->transformationTableName());
        $this->removeConfig();
    }

    /**
     * @return string
     */
    private function synchronisationTableName()
    {
        return $this->connection->getTableName($this->synchronisation->getMainTable());
    }

    /**
     * @return string
     */
    private function transformationTableName()
    {
        return $this->connection->getTableName($this->transformation->getMainTable());
    }

    /**
     * @param string $tableName
     */
    private function truncate($tableName)
    {
        $this->connection->getConnection()->query(sprintf('TRUNCATE %s', $tableName));
    }

    /**
     * @return string
     */
    private function configTableName()
    {
        return $this->connection->getTableName('core_config_data');
    }

    private function removeConfig()
    {
        $this->connection->getConnection()->delete(
            $this->configTableName(),
            "path LIKE 'cloudinary/%'"
        );
    }
}
