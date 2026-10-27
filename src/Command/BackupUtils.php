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

        $credentialsFile = self::createCredentialsFile($io, $translator, $databasePassword);
        if ($credentialsFile === null) {
            return false;
        }

        try {
            // Command to dump MySQL database. The password is not passed in the command line (it would be visible
            // to other users in the process list and in error messages) but in a temporary options file
            $command = [
                'mysqldump',
                '--defaults-extra-file=' . $credentialsFile, // it must be the first option
                '-P' . $databasePort,
                '-h' . $databaseHost,
                '-u' . $databaseUser,
                $databaseName,
                '--result-file=' . $backupFilePath, // Store output in the backup file
            ];

            $io->text($translator->trans('message.backup.info.run', ['%command%' => implode(' ', $command)], 'command') );

            return self::runCommand($io, $translator, $command);
        } finally {
            @unlink($credentialsFile);
        }
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
        $credentialsFile = self::createCredentialsFile($io, $translator, $databasePassword);
        if ($credentialsFile === null) {
            return false;
        }

        try {
            // Command to restore MySQL database (see createDatabaseBackup() about the password)
            $command = [
                'mysql',
                '--defaults-extra-file=' . $credentialsFile, // it must be the first option
                '-P' . $databasePort,
                '-h' . $databaseHost,
                '-u' . $databaseUser,
                $databaseName,
                '-e',
                'source ' . $backupFilePath,
            ];

            $io->text($translator->trans('message.restore.info.run', ['%command%' => implode(' ', $command)], 'command') );

            return self::runCommand($io, $translator, $command);
        } finally {
            @unlink($credentialsFile);
        }
    }

    /**
     * Creates a temporary MySQL options file (readable only by the current user) with the password, so it is
     * not needed in the command line. An empty password is written as empty, so the client never asks for it.
     *
     * @return string|null path of the file, which the caller must delete, or null if it could not be created
     */
    private static function createCredentialsFile(SymfonyStyle $io, TranslatorInterface $translator, string $password): ?string
    {
        $path = tempnam(sys_get_temp_dir(), 'atica-my-'); // created with 0600 permissions

        if ($path === false
            || file_put_contents($path, "[client]\npassword=\"" . addcslashes($password, "\\\"") . "\"\n") === false) {
            if ($path !== false) {
                @unlink($path);
            }
            $io->error($translator->trans('message.run_error', ['%error%' => 'credentials file'], 'command'));
            return null;
        }

        return $path;
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
