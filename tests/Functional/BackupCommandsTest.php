<?php

namespace App\Tests\Functional;

use App\Command\BackupCommand;
use App\Command\BackupRestoreCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Copia y restauración de la base de datos desde la consola. El volcado se hace de verdad sobre la base de datos de
 * pruebas (solo lectura), siempre en un directorio temporal. La restauración nunca recibe una copia real, para no
 * sobrescribir la base de datos: solo ficheros SQL inocuos que comprueban los casos de éxito y de error.
 */
class BackupCommandsTest extends KernelTestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/atica-backup-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/{,backups/}*', GLOB_BRACE) ?: [] as $file) {
            is_file($file) && unlink($file);
        }
        @rmdir($this->dir . '/backups');
        @rmdir($this->dir);
        parent::tearDown();
    }

    private function backup(): CommandTester
    {
        self::bootKernel();

        return new CommandTester(new BackupCommand($this->dir, static::getContainer()->get('translator'), static::getContainer()->get(EntityManagerInterface::class)));
    }

    private function restore(): CommandTester
    {
        self::bootKernel();

        return new CommandTester(new BackupRestoreCommand($this->dir, static::getContainer()->get('translator'), static::getContainer()->get(EntityManagerInterface::class)));
    }

    public function testBackupIsCreatedInTheDefaultPath(): void
    {
        $tester = $this->backup();

        $status = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $status, $tester->getDisplay());
        self::assertFileExists($this->dir . '/backups/backup.sql');
        self::assertStringContainsString('CREATE TABLE', (string) file_get_contents($this->dir . '/backups/backup.sql'));
    }

    public function testThePasswordIsNotPassedInTheCommandLine(): void
    {
        $tester = $this->backup();
        $password = (string) static::getContainer()->get(EntityManagerInterface::class)->getConnection()->getParams()['password'];

        $tester->execute([]);

        // la contraseña va en un fichero temporal de opciones, no en el comando (visible en la lista de procesos)
        $display = $tester->getDisplay();
        self::assertStringNotContainsString('--password', $display);
        self::assertStringNotContainsString(' -p', $display);
        if ($password !== '') {
            self::assertStringNotContainsString('-p' . $password, $display);
        }
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testBackupAcceptsFilenameAndPathOptions(): void
    {
        $tester = $this->backup();

        $status = $tester->execute(['--filename' => 'mi_copia.sql', '--path' => $this->dir]);

        self::assertSame(Command::SUCCESS, $status, $tester->getDisplay());
        self::assertFileExists($this->dir . '/mi_copia.sql');
    }

    public function testBackupWithTimestampUsesTheDateInTheName(): void
    {
        $tester = $this->backup();

        $status = $tester->execute(['--timestamp' => true, '--path' => $this->dir]);

        self::assertSame(Command::SUCCESS, $status, $tester->getDisplay());
        self::assertCount(1, glob($this->dir . '/backup_*-*-*_*-*-*.sql'));
    }

    public function testBackupAcceptsTheDestinationAsAnArgument(): void
    {
        $tester = $this->backup();
        $target = $this->dir . '/destino.sql';

        $status = $tester->execute(['backup_file_path' => [$target]]);

        self::assertSame(Command::SUCCESS, $status, $tester->getDisplay());
        self::assertFileExists($target);
    }

    public function testBackupRejectsIncompatibleOptions(): void
    {
        $tester = $this->backup();

        self::assertSame(Command::FAILURE, $tester->execute(['--timestamp' => true, '--filename' => 'x.sql', '--path' => $this->dir]));
        self::assertSame(Command::FAILURE, $tester->execute(['backup_file_path' => [$this->dir . '/a.sql'], '--filename' => 'b.sql']));
        self::assertSame(Command::FAILURE, $tester->execute(['backup_file_path' => [$this->dir . '/a.sql'], '--timestamp' => true]));
        self::assertSame([], glob($this->dir . '/*.sql'), 'No se crea ninguna copia con opciones incompatibles');
    }

    public function testBackupFailsWhenTheDestinationCannotBeCreated(): void
    {
        $tester = $this->backup();
        // un fichero ocupa el lugar del directorio de destino
        file_put_contents($this->dir . '/ocupado', 'x');

        $status = $tester->execute(['backup_file_path' => [$this->dir . '/ocupado/copia.sql']]);

        self::assertSame(Command::FAILURE, $status);
        @unlink($this->dir . '/ocupado');
    }

    public function testRestoreFailsWhenTheFileDoesNotExist(): void
    {
        $tester = $this->restore();

        self::assertSame(Command::FAILURE, $tester->execute([]), 'Sin copia en la ruta por defecto');
        self::assertSame(Command::FAILURE, $tester->execute(['backup_file_path' => [$this->dir . '/no-existe.sql']]));
        self::assertSame(Command::FAILURE, $tester->execute(['--filename' => 'no-existe.sql', '--path' => $this->dir]));
    }

    public function testRestoreRejectsIncompatibleOptions(): void
    {
        $tester = $this->restore();
        file_put_contents($this->dir . '/a.sql', 'SELECT 1;');

        self::assertSame(Command::FAILURE, $tester->execute(['backup_file_path' => [$this->dir . '/a.sql'], '--filename' => 'b.sql']));
        self::assertSame(Command::FAILURE, $tester->execute(['backup_file_path' => [$this->dir . '/a.sql'], '--path' => $this->dir]));
    }

    public function testRestoreRunsTheSqlFile(): void
    {
        $tester = $this->restore();
        file_put_contents($this->dir . '/inocuo.sql', "SELECT 1;\n");

        $status = $tester->execute(['backup_file_path' => [$this->dir . '/inocuo.sql']]);

        self::assertSame(Command::SUCCESS, $status, $tester->getDisplay());
    }

    public function testRestoreWithAFileInTheGivenPathAndName(): void
    {
        $tester = $this->restore();
        file_put_contents($this->dir . '/otro.sql', "SELECT 1;\n");

        $status = $tester->execute(['--filename' => 'otro.sql', '--path' => $this->dir]);

        self::assertSame(Command::SUCCESS, $status, $tester->getDisplay());
    }

    public function testRestoreReportsAnInvalidSqlFile(): void
    {
        $tester = $this->restore();
        file_put_contents($this->dir . '/roto.sql', "ESTO NO ES SQL;\n");

        $status = $tester->execute(['backup_file_path' => [$this->dir . '/roto.sql']]);

        self::assertSame(Command::FAILURE, $status);
    }
}
