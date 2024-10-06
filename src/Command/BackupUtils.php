<?php

namespace App\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class BackupUtils
{

    public static function createDatabaseBackup(
        SymfonyStyle $io,
        string $databaseName,
        string $databaseUser,
        string $databasePassword,
        string $databaseHost,
        string $databasePort,
        string $backupFilePath): bool
    {
        // Ensure the directory exists before creating the backup file
        $backupDir = dirname($backupFilePath);

        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                $io->error('Failed to create backup directory.');
                return false; // Failed to create directory
            }
        }

        // Command to dump MySQL database
        $command = [
            'mysqldump',
            '-P' . $databasePort,
            '-h' . $databaseHost,
            '-u' . $databaseUser,
            '-p' . $databasePassword,
            $databaseName,
            '--result-file=' . $backupFilePath, // Store output in the backup file
        ];

        $io->text('Running database backup command: ' . implode(' ', $command));

        return self::runCommand($io, $command);
    }


    public static function restoreDatabaseBackup(
        SymfonyStyle $io,
        string $databaseName,
        string $databaseUser,
        string $databasePassword,
        string $databaseHost,
        string $databasePort,
        string $backupFilePath): bool
    {
        // Command to restore MySQL database
        $command = [
            'mysql',
            '-P' . $databasePort,
            '-h' . $databaseHost,
            '-u' . $databaseUser,
            '-p' . $databasePassword,
            $databaseName,
            '-e',
            'source ' . $backupFilePath,
        ];

        $io->text('Running database restore command: ' . implode(' ', $command));

        return self::runCommand($io, $command);
    }

    public static function runCommand(SymfonyStyle $io, array $command): bool
    {
        $process = new Process($command);
        $process->setTimeout(3600); // 1 hour timeout for large migrations

        try {
            $process->mustRun();
            return true;
        } catch (\Exception $e) {
            $io->error('Error running command: ' . $e->getMessage());
            return false;
        }
    }
}
