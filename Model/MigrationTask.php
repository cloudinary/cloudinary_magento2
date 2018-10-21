<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Core\Migration\Task;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class MigrationTask implements Task
{
    const MIGRATION_RUNNING_FLAG_FILENAME = 'cloudinary_migration_running.flag';

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $flagDir;

    /**
     * Constructor
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->flagDir = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    public function hasStarted()
    {
        return $this->flagDir->isExist(self::MIGRATION_RUNNING_FLAG_FILENAME);
    }

    public function hasBeenStopped()
    {
        return !$this->hasStarted();
    }

    public function stop()
    {
        $this->flagDir->delete(self::MIGRATION_RUNNING_FLAG_FILENAME);
    }

    public function start()
    {
        $this->flagDir->touch(self::MIGRATION_RUNNING_FLAG_FILENAME);
    }
}
