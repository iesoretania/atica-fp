<?php

namespace App\Tests\Functional;

use App\Command\BackupUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Copia de seguridad y restauración con mysqldump y mysql reales: la base de datos de tests se copia a un
 * fichero, se restaura en una base de datos temporal y se comprueba que tiene los mismos datos.
 */
class BackupRoundTripTest extends AppWebTestCase
{
    public function testBackupAndRestoreReproduceTheDatabase(): void
    {
        $finder = new ExecutableFinder();
        if ($finder->find('mysqldump') === null || $finder->find('mysql') === null) {
            self::markTestSkipped('Los programas mysqldump y mysql no están instalados');
        }

        $params = static::getContainer()->get('doctrine')->getConnection()->getParams();
        $file = tempnam(sys_get_temp_dir(), 'atica-backup-') . '.sql';
        $scratch = $params['dbname'] . '_roundtrip';
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);
        $translator = static::getContainer()->get('translator');

        // conexión independiente de la de los tests (que está dentro de una transacción) para crear y borrar la base de datos
        $admin = DriverManager::getConnection(array_diff_key($params, ['dbname' => 1]));
        $admin->executeStatement("DROP DATABASE IF EXISTS `$scratch`");
        $admin->executeStatement("CREATE DATABASE `$scratch`");

        try {
            self::assertTrue(
                BackupUtils::createDatabaseBackup(
                    $io,
                    $translator,
                    $params['dbname'],
                    $params['user'],
                    (string) ($params['password'] ?? ''),
                    $params['host'],
                    (string) ($params['port'] ?? '3306'),
                    $file
                ),
                $output->fetch()
            );
            self::assertGreaterThan(10000, filesize($file), 'La copia de seguridad está vacía');

            self::assertTrue(
                BackupUtils::restoreDatabaseBackup(
                    $io,
                    $translator,
                    $scratch,
                    $params['user'],
                    (string) ($params['password'] ?? ''),
                    $params['host'],
                    (string) ($params['port'] ?? '3306'),
                    $file
                ),
                $output->fetch()
            );

            $restored = DriverManager::getConnection($params + []);
            $restored->executeStatement("USE `$scratch`");
            $original = static::getContainer()->get('doctrine')->getConnection();

            foreach (['person', 'edu_teacher', 'edu_student_enrollment', 'wpt_work_day', 'wlt_agreement', 'itp_student_program_workcenter'] as $table) {
                self::assertGreaterThan(0, (int) $original->fetchOne("SELECT COUNT(*) FROM `$table`"), $table);
                self::assertSame(
                    $original->fetchOne("SELECT COUNT(*) FROM `$table`"),
                    $this->rowCount($restored, $scratch, $table),
                    "La tabla $table no se ha restaurado igual"
                );
            }
            // y un dato concreto, no solo el número de filas
            self::assertSame(
                $original->fetchOne("SELECT password FROM person WHERE login_username = 'admin'"),
                $restored->fetchOne("SELECT password FROM `$scratch`.person WHERE login_username = 'admin'")
            );
        } finally {
            $admin->executeStatement("DROP DATABASE IF EXISTS `$scratch`");
            @unlink($file);
            @unlink(substr($file, 0, -4));
        }
    }

    private function rowCount(Connection $connection, string $database, string $table): mixed
    {
        return $connection->fetchOne("SELECT COUNT(*) FROM `$database`.`$table`");
    }
}
