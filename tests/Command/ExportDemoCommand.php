<?php

namespace App\Tests\Command;

use App\Command\BackupUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Genera los datos de demostración (docker/demo.sql) a partir de la base de datos de las fixtures.
 *
 * Solo existe en el entorno de tests. Se usa con: composer demo:regenerate
 *
 * Las fechas de las prácticas se calculan a partir del día en que se cargan las fixtures, por lo que
 * conviene regenerar el volcado de vez en cuando para que los datos de demostración sean recientes.
 */
#[AsCommand(
    name: 'app:demo:export',
    description: 'Exporta la base de datos de las fixtures como datos de demostración (docker/demo.sql)'
)]
class ExportDemoCommand extends Command
{
    /** Contraseña del administrador de la demostración (la que documenta el README) */
    public const ADMIN_PASSWORD = 'aticafp';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::OPTIONAL, 'Fichero de destino, relativo al proyecto', 'docker/demo.sql');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->em->getConnection();
        $params = $connection->getParams();

        // solo se exporta la base de datos de tests (con las fixtures), nunca una con datos reales
        if (!str_contains((string) $params['dbname'], '_test')) {
            $io->error(sprintf('Solo se puede exportar una base de datos de tests (*_test); la actual es "%s".', $params['dbname']));
            return Command::FAILURE;
        }

        $original = $connection->fetchOne("SELECT password FROM person WHERE login_username = 'admin'");
        if ($original === false) {
            $io->error('No existe el usuario "admin": carga primero las fixtures con composer test:reset-db');
            return Command::FAILURE;
        }

        $file = $this->projectDir . '/' . $input->getArgument('file');
        $connection->executeStatement(
            "UPDATE person SET password = ? WHERE login_username = 'admin'",
            // con el coste de producción, para que la aplicación no tenga que recalcular el hash al entrar
            [password_hash(self::ADMIN_PASSWORD, PASSWORD_BCRYPT, ['cost' => 13])]
        );

        try {
            $exported = BackupUtils::createDatabaseBackup(
                $io,
                $this->translator,
                $params['dbname'],
                $params['user'],
                (string) ($params['password'] ?? ''),
                $params['host'],
                (string) ($params['port'] ?? '3306'),
                $file
            );
        } finally {
            // la base de datos de tests queda como estaba
            $connection->executeStatement("UPDATE person SET password = ? WHERE login_username = 'admin'", [$original]);
        }

        if (!$exported) {
            return Command::FAILURE;
        }

        $io->success(sprintf('Datos de demostración exportados a %s (%d KB)', $input->getArgument('file'), (int) (filesize($file) / 1024)));

        return Command::SUCCESS;
    }
}
