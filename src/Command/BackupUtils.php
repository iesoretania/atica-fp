<?php

namespace App\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackupUtils
{
    public static function createDatabaseBackup(
        SymfonyStyle        $io,
        TranslatorInterface $translator,
        string              $databaseName,
        string              $databaseUser,
        string              $databasePassword,
        string              $databaseHost,
        string              $databasePort,
        string              $backupFilePath): bool
    {
        // Ensure the directory exists before creating the backup file
        $backupDir = dirname($backupFilePath);

        if (!is_dir($backupDir)) {
            if (is_file($backupDir)
                || (!mkdir($backupDir, 0755, true) && !is_dir($backupDir))) {
                $io->error($translator->trans('message.backup.error.backup_dir', ['%dir%' => $backupDir], 'command'));
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

        $commandCopy = $command;
        $commandCopy[4] = '-p*****'; // Hide password in the command output

        $io->text($translator->trans('message.backup.info.run', ['%command%' => implode(' ', $commandCopy)], 'command') );

        return self::runCommand($io, $translator, $command);
    }


    public static function restoreDatabaseBackup(
        SymfonyStyle        $io,
        TranslatorInterface $translator,
        string              $databaseName,
        string              $databaseUser,
        string              $databasePassword,
        string              $databaseHost,
        string              $databasePort,
        string              $backupFilePath): bool
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

        $commandCopy = $command;
        $commandCopy[4] = '-p*****'; // Hide password in the command output

        $io->text($translator->trans('message.restore.info.run', ['%command%' => implode(' ', $commandCopy)], 'command') );

        return self::runCommand($io, $translator, $command);
    }

    public static function runCommand(SymfonyStyle $io, TranslatorInterface $translator, array $command): bool
    {
        $process = new Process($command);
        $process->setTimeout(3600); // 1 hour timeout for large migrations

        try {
            $process->mustRun();
            return true;
        } catch (\Exception $e) {
            $io->error($translator->trans('message.run_error', ['%error%' => $e->getMessage()], 'command'));
            return false;
        }
    }

    public static function recreateDatabase(SymfonyStyle $io, OutputInterface $output, TranslatorInterface $translator, Application $application): bool
    {
        // Drop and recreate database
        try {
            $io->section($translator->trans('title.recreate', [], 'command'));

            // This runs the `doctrine:database:drop` command
            $dropDatabaseCommand = $application->find('doctrine:database:drop');
            $argv = [$_SERVER['argv'][0], 'doctrine:database:drop', '--force', '-n'];
            $input2 = new ArgvInput($argv);
            $input2->setInteractive(false);
            $result = $dropDatabaseCommand->run($input2, $output);
            if ($result !== 0) {
                $io->error($translator->trans('message.recreate.error', ['%error' => $result], 'command'));
                return false;
            }

            // This runs the `doctrine:database:create` command
            $createDatabaseCommand = $application->find('doctrine:database:create');
            $argv = [$_SERVER['argv'][0], 'doctrine:database:create', '-n'];
            $input2 = new ArgvInput($argv);
            $input2->setInteractive(false);
            $result = $createDatabaseCommand->run($input2, $output);
            if ($result !== 0) {
                $io->error($translator->trans('message.recreate.error', ['%error' => $result], 'command'));
                return false;
            }
            $io->success($translator->trans('message.recreate.success', [], 'command'));
        } catch (\Exception $e) {
            $io->error($translator->trans('message.recreate.error', ['%error' => $e->getMessage()], 'command'));
            return false;
        }
        return true;
    }
}
