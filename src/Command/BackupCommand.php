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
        $this->backupFilePath = $this->backupPath . '/' . $this->backupFilename; // Default filepath
    }

    public function configure(): void
    {
        $this
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'Backup filename')
            ->addOption('timestamp', null, InputOption::VALUE_NONE, 'Uses current date & time as backup filename')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Backup destination path, relative to current directory');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize $io here
        $this->io = new SymfonyStyle($input, $output);

        if ($input->getOption('timestamp')) {
            if ($input->getOption('filename')) {
                $this->io->error('Cannot use both --filename and --timestamp options together.');
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
            $this->io->error('Failed to create database snapshot.');
            return Command::FAILURE;
        }
        $this->io->success('Database snapshot created successfully into ' . $this->backupFilePath);

        return Command::SUCCESS;
    }
}
