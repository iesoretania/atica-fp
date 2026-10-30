<?php

namespace App\Tests\Functional;

use App\Command\SafeMigrateCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Migración segura: hace una copia de la base de datos, migra y borra la copia salvo que se pida conservarla.
 * La copia contiene todos los datos (incluidos los hashes de las contraseñas), por lo que no debe quedar olvidada.
 * El test hace un volcado real de la base de datos de pruebas en un directorio temporal, pero sustituye la
 * migración por una orden vacía: no se recorre la ruta de restauración, que sobrescribiría la base de datos.
 */
class SafeMigrateCommandTest extends KernelTestCase
{
    private string $dir;

    /** @var array<int, string>|null */
    private ?array $argv = null;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/atica-safe-migrate-' . bin2hex(random_bytes(4));
        // la orden pasa los argumentos del proceso a la migración: se aíslan de los de PHPUnit
        $this->argv = $_SERVER['argv'] ?? null;
        $_SERVER['argv'] = ['bin/console', 'app:safe-migrate'];
    }

    protected function tearDown(): void
    {
        $_SERVER['argv'] = $this->argv;
        @unlink($this->dir . '/backups/migration_backup.sql');
        @rmdir($this->dir . '/backups');
        @rmdir($this->dir);
        parent::tearDown();
    }

    private function tester(): CommandTester
    {
        self::bootKernel();
        $container = static::getContainer();

        $application = new Application();
        $application->setAutoExit(false);
        $application->add(new SafeMigrateCommand($this->dir, $container->get('translator'), $container->get(EntityManagerInterface::class)));
        $application->add((new Command('doctrine:migrations:migrate'))->setCode(static fn(): int => Command::SUCCESS));

        return new CommandTester($application->find('app:safe-migrate'));
    }

    public function testSuccessfulMigrationRemovesTheBackup(): void
    {
        $tester = $this->tester();

        $status = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $status, $tester->getDisplay());
        self::assertFileDoesNotExist($this->dir . '/backups/migration_backup.sql', 'La copia no debe quedar guardada');
    }

    public function testKeepBackupOptionKeepsTheBackup(): void
    {
        $tester = $this->tester();

        $status = $tester->execute(['--keep-backup' => true]);

        self::assertSame(Command::SUCCESS, $status, $tester->getDisplay());
        self::assertFileExists($this->dir . '/backups/migration_backup.sql');
        self::assertGreaterThan(0, filesize($this->dir . '/backups/migration_backup.sql'));
    }
}
