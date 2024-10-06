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
    name: 'app:backup-restore',
    description: 'Restore backup of application data.')]
class BackupRestoreCommand extends Command
{
    private string $databaseName;
    private string $databaseUser;
    private string $databasePassword;
    private string $databaseHost;
    private string $databasePort;
    private string $backupFilename;
    private string $backupPath;
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

        // Define default backup file path relative to the project root directory
        $this->backupPath = $this->projectDir . '/backups'; // Default path
        $this->backupFilename = 'backup.sql'; // Default filename
        $this->backupFilePath = $this->backupPath . $this->backupFilename; // Default filepath
    }

    protected function configure(): void
    {
        $this
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'Backup filename (default: backup.sql)')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Backup destination path, relative to current directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize $io here
        $this->io = new SymfonyStyle($input, $output);

        if ($input->getOption('filename')) {
            $this->backupFilename = $input->getOption('filename');
        }

        if ($input->getOption('path')) {
            $this->backupPath = $input->getOption('path');
        }

        // Ensure the directory exists before creating the backup file
        $backupDir = dirname($this->backupFilePath);

        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                $this->io->error('Failed to create backup directory.');
                return Command::FAILURE; // Failed to create directory
            }
        }

        $this->backupFilePath = $this->backupPath . '/' . $this->backupFilename;

        // Restore database
        if (!BackupUtils::restoreDatabaseBackup(
            $this->io,
            $this->databaseName,
            $this->databaseUser,
            $this->databasePassword,
            $this->databaseHost,
            $this->databasePort,
            $this->backupFilePath)
        ) {
            $this->io->error('Failed to restore the database from snapshot.');
            return Command::FAILURE;
        }

        $this->io->success('Database snapshot restored successfully from ' . $this->backupFilePath);
        return Command::SUCCESS;
    }
}
