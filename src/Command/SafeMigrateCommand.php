<?php
/**
 * Based on code made by Patric Gutersohn (patric.gutersohn@gmx.de)
 */
namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

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

        // Define the backup file path relative to the project root directory
        $this->backupFilePath = $this->projectDir . '/backups/migration_backup.sql';
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize $io here
        $this->io = new SymfonyStyle($input, $output);

        // Step 1: Create database snapshot
        $this->io->section($this->translator->trans('title.migration.backup', [], 'command'));
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
            $this->io->error($this->translator->trans('message.migration.error.backup', [], 'command'));
            return Command::FAILURE;
        }
        $this->io->success($this->translator->trans('message.migration.success.backup', [], 'command'));

        // Step 2: Run Doctrine migration
        $this->io->section($this->translator->trans('title.migration.migrate', [], 'command'));
        try {
            // This runs the `doctrine:migrations:migrate` command
            $migrateCommand = $this->getApplication()->find('doctrine:migrations:migrate');
            $migrateCommand->run($input, $output);
        } catch (\Exception $e) {
            $this->io->error($this->translator->trans('message.migration.error.migrate', ['%error' => $e->getMessage()], 'command'));
            $this->io->section($this->translator->trans('title.migration.restore', [], 'command'));

            // Step 3: Restore database if migration fails
            if (BackupUtils::restoreDatabaseBackup(
                $this->io,
                $this->translator,
                $this->databaseName,
                $this->databaseUser,
                $this->databasePassword,
                $this->databaseHost,
                $this->databasePort,
                $this->backupFilePath)
            ) {
                $this->io->success($this->translator->trans('message.migration.success.restore', [], 'command'));
                if (!$removeBackup) {
                    $this->removeBackup();
                }
            } else {
                $this->io->error($this->translator->trans('message.migration.error.restore', [], 'command'));
            }
            return Command::FAILURE;
        }

        $this->io->success($this->translator->trans('message.migration.success.migrate', [], 'command'));

        $this->removeBackup();

        return Command::SUCCESS;
    }

    public function removeBackup(): void
    {
        if (!unlink($this->backupFilePath)) {
            $this->io->warning($this->translator->trans('message.migration.warning.backup_removal', ['%file%' => $this->backupFilePath], 'command'));
        } else {
            $this->io->success($this->translator->trans('message.migration.success.backup_removal', ['%file%' => $this->backupFilePath], 'command'));
        }
    }
}
