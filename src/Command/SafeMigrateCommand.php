<?php
/**
 * Based on code made by Patric Gutersohn (patric.gutersohn@gmx.de)
 */
namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:safe-migrate',
    description: 'Run Doctrine migrations with a backup. If migration fails, restores the previous state.')]
class SafeMigrateCommand extends Command
{
    private string $databaseName;
    private string $databaseUser;
    private string $databasePassword;
    private string $databaseHost;
    private string $databasePort;
    private string $backupFilePath;

    // Define $io as a class property
    private SymfonyStyle $io;

    public function __construct(private readonly string $projectDir, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->databaseName = $em->getConnection()->getDatabase();
        $this->databaseUser = $em->getConnection()->getParams()['user'];
        $this->databasePassword = $em->getConnection()->getParams()['password'];
        $this->databaseHost = $em->getConnection()->getParams()['host'];
        $this->databasePort = $em->getConnection()->getParams()['port'] ?? '3306';

        // Define the backup file path relative to the project root directory
        $this->backupFilePath = $this->projectDir . '/backups/migration_backup.sql';
    }

    protected function configure(): void
    {
        $this
            ->addOption('no-backup-removal', null, InputOption::VALUE_NONE, 'Do not remove the backup file after migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize $io here
        $this->io = new SymfonyStyle($input, $output);

        // Step 1: Create database snapshot
        $this->io->section('Creating database snapshot...');
        if (!BackupUtils::createDatabaseBackup(
            $this->io,
            $this->databaseName,
            $this->databaseUser,
            $this->databasePassword,
            $this->databaseHost,
            $this->databasePort,
            $this->backupFilePath
        )) {
            $this->io->error('Failed to create database snapshot. Aborting migration.');
            return Command::FAILURE;
        }
        $this->io->success('Database snapshot created successfully.');

        // Step 2: Run Doctrine migration
        $this->io->section('Running Doctrine migrations...');
        try {
            // This runs the `doctrine:migrations:migrate` command
            $migrateCommand = $this->getApplication()->find('doctrine:migrations:migrate');
            $migrateCommand->run($input, $output);
        } catch (\Exception $e) {
            $this->io->error('Migration failed: ' . $e->getMessage());
            $this->io->section('Restoring database from snapshot...');

            // Step 3: Restore database if migration fails
            if (BackupUtils::restoreDatabaseBackup(
                $this->io,
                $this->databaseName,
                $this->databaseUser,
                $this->databasePassword,
                $this->databaseHost,
                $this->databasePort,
                $this->backupFilePath)
            ) {
                $this->io->success('Database restored successfully after mailed migration.');
                if (!$input->getOption('no-backup-removal')) {
                    $this->removeBackup();
                }
            } else {
                $this->io->error('Failed to restore the database from snapshot.');
            }
            return Command::FAILURE;
        }

        $this->io->success('Migration completed successfully.');

        if (!$input->getOption('no-backup-removal')) {
            $this->removeBackup();
        }

        return Command::SUCCESS;
    }

    public function removeBackup(): void
    {
        if (!unlink($this->backupFilePath)) {
            $this->io->warning('Failed to delete the database snapshot file.');
        } else {
            $this->io->success('Database snapshot file deleted successfully.');
        }
    }
}
