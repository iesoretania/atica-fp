<?php
/**
 * Based on code made by Patric Gutersohn (patric.gutersohn@gmx.de)
 */
namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function __construct(
        private readonly string              $projectDir,
        private readonly TranslatorInterface $translator,
        EntityManagerInterface               $em
    ) {
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
            ->addArgument('backup_file_path', InputOption::VALUE_OPTIONAL, 'Path to the backup file to restore')
            ->addOption('filename', null, InputOption::VALUE_REQUIRED, 'Backup filename (default: backup.sql)')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Backup destination path, relative to current directory')
            ->addOption('recreate-database', null, InputOption::VALUE_NONE, 'Recreate the database before restoring the backup');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize $io here
        $this->io = new SymfonyStyle($input, $output);

        if ($input->getArgument('backup_file_path')) {
            if ($input->getOption('filename') || $input->getOption('path')) {
                $this->io->error($this->translator->trans('message.restore.error.options', [], 'command'));
                return Command::FAILURE;
            }
            $this->backupFilePath = $input->getArgument('backup_file_path')[0];
        } else {
            if ($input->getOption('filename')) {
                $this->backupFilename = $input->getOption('filename');
            }

            if ($input->getOption('path')) {
                $this->backupPath = $input->getOption('path');
            }
            $this->backupFilePath = $this->backupPath . '/' . $this->backupFilename;
        }

        if (!is_file($this->backupFilePath)) {
            $this->io->error($this->translator->trans('message.restore.error.file_not_found', ['%file%' => $this->backupFilePath], 'command'));
            return Command::FAILURE;
        }

        if ($input->getOption('recreate-database') && !BackupUtils::recreateDatabase($this->io, $output, $this->translator, $this->getApplication())) {
            return Command::FAILURE;
        }

        $this->io->section($this->translator->trans('title.restore', [], 'command'));
        // Restore database
        if (!BackupUtils::restoreDatabaseBackup(
            $this->io,
            $this->translator,
            $this->databaseName,
            $this->databaseUser,
            $this->databasePassword,
            $this->databaseHost,
            $this->databasePort,
            $this->backupFilePath)
        ) {
            $this->io->error($this->translator->trans('message.restore.error', ['%file%' => $this->backupFilePath], 'command'));
            return Command::FAILURE;
        }

        $this->io->success($this->translator->trans('message.restore.success', ['%file%' => $this->backupFilePath], 'command'));
        return Command::SUCCESS;
    }
}
