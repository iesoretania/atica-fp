<?php

namespace App\Tests\Command;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Group;
use App\Entity\Edu\Department;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\Organization;
use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Convierte la base de datos de las fixtures en los datos de demostración del manual de usuario: nombres,
 * apellidos y usuarios creíbles, un centro con nombre propio y bastante más profesorado y alumnado, para que
 * las listas de las capturas no estén vacías.
 *
 * Solo existe en el entorno de tests y solo actúa sobre una base de datos del manual (con «manual» en su
 * nombre), nunca sobre la de desarrollo ni sobre una con datos reales. Se usa a través de
 * `make docs-data` (ver docs/manual/CAPTURAS.md), que recrea la base de datos, carga las fixtures y ejecuta
 * este comando.
 *
 * Todos los usuarios tienen la misma contraseña (DemoData::PASSWORD). Los usuarios por perfil están en
 * PROFILES, que también lee el catálogo de las capturas.
 */
#[AsCommand(
    name: 'app:manual:load-data',
    description: 'Convierte la base de datos de las fixtures en los datos de demostración del manual de usuario'
)]
class LoadManualDataCommand extends Command
{
    public const ORGANIZATION_NAME = 'I.E.S. Ada Lovelace';
    public const ORGANIZATION_CODE = '29700456';

    /**
     * Usuario de demostración de cada perfil: nombre de usuario de las fixtures => [usuario, nombre, apellidos]
     *
     * @var array<string, array{string, string, string}>
     */
    public const PROFILES = [
        'alfa.admin' => ['avalverde', 'Antonio', 'Valverde Ruiz'],
        'alfa.director' => ['lortega', 'Lucía', 'Ortega Molina'],
        'alfa.economico' => ['ireyes', 'Inmaculada', 'Reyes Pardo'],
        'alfa.jefe' => ['rcabrera', 'Ramón', 'Cabrera Soler'],
        'alfa.tutor' => ['mjimenez', 'Marta', 'Jiménez Aranda'],
        'alfa.profesor' => ['jbarrios', 'Julián', 'Barrios Cortés'],
        'alfa.coordinador' => ['esalas', 'Elena', 'Salas Quintana'],
        'alfa.gestorffe' => ['therrero', 'Tomás', 'Herrero Lara'],
        'alfa.tutorlaboral' => ['snavarro', 'Sergio', 'Navarro Gil'],
        'alfa.tutorlaboral2' => ['pduran', 'Patricia', 'Durán Vela'],
        'alfa.alumno1' => ['dfernandez', 'Daniel', 'Fernández Ortiz'],
        'alfa.alumno2' => ['cromero', 'Carla', 'Romero Díaz'],
        'alfa.alumno3' => ['hmedina', 'Hugo', 'Medina Sáez'],
        'alfa.alumno4' => ['ncampos', 'Noa', 'Campos León'],
        'alfa.alumno5' => ['apastor', 'Alejandro', 'Pastor Vega'],
        'alfa.alumno6' => ['ilozano', 'Irene', 'Lozano Cruz'],
        'alfa.alumno7' => ['aibanez', 'Adrián', 'Ibáñez Mora'],
        'alfa.alumno8' => ['sgallego', 'Sara', 'Gallego Roldán'],
    ];

    private const FIRST_NAMES = [
        'Lucas', 'Martina', 'Mateo', 'Valeria', 'Pablo', 'Julia', 'Álvaro', 'Paula', 'Diego', 'Claudia',
        'Javier', 'Alba', 'Rubén', 'Marina', 'Iván', 'Natalia', 'Óscar', 'Elena', 'Marcos', 'Andrea',
        'Samuel', 'Nuria', 'Guillermo', 'Lola', 'Raúl', 'Aitana', 'Víctor', 'Ainhoa', 'Bruno', 'Celia',
        'Joaquín', 'Rocío', 'Gonzalo', 'Beatriz', 'Eduardo', 'Cristina', 'Ismael', 'Laura', 'Rafael', 'Mónica',
    ];

    private const SURNAMES = [
        'García', 'Martínez', 'López', 'Sánchez', 'Pérez', 'Gómez', 'Ruiz', 'Hernández', 'Jiménez', 'Díaz',
        'Moreno', 'Álvarez', 'Muñoz', 'Romero', 'Alonso', 'Gutiérrez', 'Navarro', 'Torres', 'Domínguez', 'Vázquez',
        'Ramos', 'Gil', 'Serrano', 'Blanco', 'Molina', 'Morales', 'Suárez', 'Ortega', 'Delgado', 'Castro',
        'Ortiz', 'Rubio', 'Marín', 'Sanz', 'Núñez', 'Iglesias', 'Medina', 'Garrido', 'Cortés', 'Castillo',
    ];

    /** Profesorado y alumnado que se añade al grupo de segundo y al de primero */
    private const EXTRA_TEACHERS = 9;
    private const EXTRA_STUDENTS_SECOND = 14;
    private const EXTRA_STUDENTS_FIRST = 20;

    private int $nameCursor = 0;

    /** @var array<string, true> */
    private array $usernames = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->em->getConnection();
        $database = (string) $connection->getParams()['dbname'];

        if (!str_contains($database, 'manual')) {
            $io->error(sprintf('Solo se puede ejecutar sobre una base de datos del manual (con «manual» en el nombre); la actual es "%s".', $database));
            return Command::FAILURE;
        }
        if ($connection->fetchOne('SELECT COUNT(*) FROM person WHERE login_username = ?', ['avalverde']) > 0) {
            $io->error('Los datos del manual ya están aplicados: recrea la base de datos (make docs-data).');
            return Command::FAILURE;
        }
        if ($connection->fetchOne('SELECT COUNT(*) FROM person WHERE login_username = ?', ['alfa.admin']) === '0') {
            $io->error('Faltan las fixtures: carga primero las de tests en esta base de datos.');
            return Command::FAILURE;
        }

        foreach (self::PROFILES as [$username]) {
            $this->usernames[$username] = true;
        }

        $this->addPeople($io);
        $this->rename($connection);

        $io->success('Datos del manual aplicados. Contraseña de todos los usuarios: ' . DemoData::PASSWORD);

        return Command::SUCCESS;
    }

    private function addPeople(SymfonyStyle $io): void
    {
        $organization = $this->em->getRepository(Organization::class)->findOneBy(['code' => DemoData::ORG_A_CODE]);
        $academicYear = $organization->getCurrentAcademicYear();
        assert($academicYear instanceof AcademicYear);

        $departments = $this->em->getRepository(Department::class)->findBy(['academicYear' => $academicYear]);
        $groups = $this->em->getRepository(Group::class)->createQueryBuilder('g')
            ->join('g.grade', 'gr')->join('gr.training', 't')
            ->where('t.academicYear = :year')->setParameter('year', $academicYear)
            ->orderBy('gr.name')->getQuery()->getResult();
        [$first, $second] = [$groups[0], $groups[1]];

        for ($i = 0; $i < self::EXTRA_TEACHERS; $i++) {
            $teacher = new Teacher();
            $teacher
                ->setPerson($this->person())
                ->setAcademicYear($academicYear)
                ->setDepartment($departments[$i % count($departments)]);
            $this->em->persist($teacher);
        }

        foreach ([[$second, self::EXTRA_STUDENTS_SECOND], [$first, self::EXTRA_STUDENTS_FIRST]] as [$group, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $enrollment = new StudentEnrollment();
                $enrollment->setPerson($this->person())->setGroup($group);
                $this->em->persist($enrollment);
            }
        }

        $this->em->flush();
        $io->text(sprintf('Añadidos %d docentes y %d estudiantes.', self::EXTRA_TEACHERS, self::EXTRA_STUDENTS_SECOND + self::EXTRA_STUDENTS_FIRST));
    }

    /**
     * Persona con nombre y apellidos distintos de las anteriores, y un usuario derivado de ellos
     */
    private function person(): Person
    {
        $i = $this->nameCursor++;
        $firstName = self::FIRST_NAMES[$i % count(self::FIRST_NAMES)];
        // el desfase evita que los mismos nombre y apellido se repitan de forma ordenada
        $surname1 = self::SURNAMES[($i * 7 + 3) % count(self::SURNAMES)];
        $surname2 = self::SURNAMES[($i * 11 + 5) % count(self::SURNAMES)];

        $username = $this->username($firstName, $surname1);

        $person = new Person();
        $person
            ->setLoginUsername($username)
            // el identificador es el IdEA del profesorado y del alumnado
            ->setUniqueIdentifier($username)
            ->setFirstName($firstName)
            ->setLastName($surname1 . ' ' . $surname2)
            ->setEnabled(true)
            ->setGlobalAdministrator(false)
            ->setForcePasswordChange(false)
            ->setExternalCheck(false)
            ->setAllowExternalCheck(false);
        $person->setEmailAddress($person->getLoginUsername() . '@' . self::emailDomain());
        $person->setPassword($this->hasher->hashPassword($person, DemoData::PASSWORD));
        $this->em->persist($person);

        return $person;
    }

    private function username(string $firstName, string $surname): string
    {
        $base = self::ascii(mb_substr($firstName, 0, 1) . $surname);
        $username = $base;
        for ($n = 2; isset($this->usernames[$username]); $n++) {
            $username = $base . $n;
        }
        $this->usernames[$username] = true;

        return $username;
    }

    private static function ascii(string $text): string
    {
        return strtolower(strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N', ' ' => '',
        ]));
    }

    private static function emailDomain(): string
    {
        return 'adalovelace.example';
    }

    private function rename(Connection $connection): void
    {
        $connection->beginTransaction();
        try {
            foreach (self::PROFILES as $old => [$username, $firstName, $lastName]) {
                $connection->executeStatement(
                    'UPDATE person SET login_username = ?, unique_identifier = ?, first_name = ?, last_name = ?, email_address = ? WHERE login_username = ?',
                    [$username, $username, $firstName, $lastName, $username . '@' . self::emailDomain(), $old]
                );
            }
            $connection->executeStatement(
                'UPDATE organization SET name = ?, code = ?, city = ?, address = ?, zip_code = ?, phone_number = ?, email_address = ? WHERE code = ?',
                [self::ORGANIZATION_NAME, self::ORGANIZATION_CODE, 'Linares', 'Calle Ramón y Cajal, 12', '23700', '953 00 00 00', 'direccion@' . self::emailDomain(), DemoData::ORG_A_CODE]
            );
            $connection->executeStatement(
                'UPDATE organization SET name = ?, code = ?, city = ? WHERE code = ?',
                ['I.E.S. Miguel Hernández', '29700457', 'Úbeda', DemoData::ORG_B_CODE]
            );
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
