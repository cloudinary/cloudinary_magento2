<?php

namespace Cloudinary\Cloudinary\Command;

use Cloudinary\Cloudinary\Model\MigrationTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopMigration extends Command
{
    const NOP_MESSAGE = 'No upload/download running to stop.';
    const STOPPED_MESSAGE = 'Upload/Download manually stopped.';

    /**
     * @var MigrationTask
     */
    private $migrationTask;

    /**
     * @param MigrationTask $migrationTask
     */
    public function __construct(MigrationTask $migrationTask)
    {
        parent::__construct('cloudinary:migration:stop');

        $this->migrationTask = $migrationTask;
    }

    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Stops any currently running upload/download.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->migrationTask->hasStarted()) {
            $this->migrationTask->stop();
            $output->writeln(self::STOPPED_MESSAGE);
        } else {
            $output->writeln(self::NOP_MESSAGE);
        }
    }
}
