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
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:backup',
    description: 'Generate backup of application data.')]
class BackupCommand extends Command
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
        EntityManagerInterface               $em,
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
        $this->backupFilePath = $this->backupPath . '/' . $this->backupFilename; // Default filepath
    }

    public function configure(): void
    {
        $this
            ->addArgument('backup_file_path', InputOption::VALUE_OPTIONAL, 'Path to the backup file to store the snapshot')
            ->addOption('filename', null, InputOption::VALUE_REQUIRED, 'Backup filename')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Backup destination path, relative to current directory')
            ->addOption('timestamp', null, InputOption::VALUE_NONE, 'Uses current date & time as backup filename');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize $io here
        $this->io = new SymfonyStyle($input, $output);

        if ($input->getArgument('backup_file_path')) {
            if ($input->getOption('filename') || $input->getOption('path') || $input->getOption('timestamp')) {
                $this->io->error($this->translator->trans('message.backup.error.options.backup_file_name', [], 'command'));
                return Command::FAILURE;
            }
            $this->backupFilePath = $input->getArgument('backup_file_path')[0];
        } else {
            if ($input->getOption('timestamp')) {
                if ($input->getOption('filename')) {
                    $this->io->error($this->translator->trans('message.backup.error.options.timestamp_and_filename', [], 'command'));
                    return Command::FAILURE;
                }
                $timestamp = date('Y-m-d_H-i-s');
                $this->backupFilename = 'backup_' . $timestamp . '.sql';
            } elseif ($input->getOption('filename')) {
                $this->backupFilename = $input->getOption('filename');
            }

            if ($input->getOption('path')) {
                $this->backupPath = $input->getOption('path');
            }

            $this->backupFilePath = $this->backupPath . '/' . $this->backupFilename;
        }

        // Step 1: Create database snapshot
        $this->io->section($this->translator->trans('title.backup', [], 'command'));
        if (!BackupUtils::createDatabaseBackup(
            $this->io,
            $this->translator,
            $this->databaseName,
            $this->databaseUser,
            $this->databasePassword,
            $this->databaseHost,
            $this->databasePort,
            $this->backupFilePath
        )) {
            $this->io->error($this->translator->trans('message.backup.error', [], 'command'));
            return Command::FAILURE;
        }
        $this->io->success($this->translator->trans('message.backup.success', ['%file%' => $this->backupFilePath], 'command'));

        return Command::SUCCESS;
    }
}
