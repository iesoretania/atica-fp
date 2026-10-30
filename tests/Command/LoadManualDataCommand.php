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

    /**
     * Personas del segundo centro (I.E.S. Miguel Hernández), que salen en los listados de la administración de
     * la plataforma: nombre de usuario de las fixtures => [usuario, nombre, apellidos]
     *
     * @var array<string, array{string, string, string}>
     */
    private const SECOND_CENTER_PROFILES = [
        'beta.admin' => ['cmolina', 'Cristina', 'Molina Herrera'],
        'beta.director' => ['gmarin', 'Gloria', 'Marín Lozano'],
        'beta.economico' => ['ocastro', 'Óscar', 'Castro Prieto'],
        'beta.jefe' => ['fvidal', 'Fernando', 'Vidal Rojas'],
        'beta.tutor' => ['rsoto', 'Rosa', 'Soto Camacho'],
        'beta.profesor' => ['mbravo', 'Manuel', 'Bravo Ibáñez'],
        'beta.coordinador' => ['vleon', 'Verónica', 'León Cano'],
        'beta.gestorffe' => ['jparra', 'Jorge', 'Parra Duarte'],
        'beta.tutorlaboral' => ['dcarmona', 'David', 'Carmona Pons'],
        'beta.tutorlaboral2' => ['lmendez', 'Laura', 'Méndez Gil'],
        'beta.alumno1' => ['jsantos', 'Javier', 'Santos Moya'],
        'beta.alumno2' => ['mpascual', 'Marta', 'Pascual Rey'],
        'beta.alumno3' => ['ecano', 'Eric', 'Cano Vázquez'],
        'beta.alumno4' => ['nduran', 'Nuria', 'Durán Salas'],
        'beta.alumno5' => ['bcrespo', 'Bruno', 'Crespo Lara'],
        'beta.alumno6' => ['ahidalgo', 'Alicia', 'Hidalgo Mena'],
        'beta.alumno7' => ['pescudero', 'Pablo', 'Escudero Ríos'],
        'beta.alumno8' => ['isanz', 'Inés', 'Sanz Llorente'],
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

        foreach ([...self::PROFILES, ...self::SECOND_CENTER_PROFILES] as [$username]) {
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
            foreach (self::SECOND_CENTER_PROFILES as $old => [$username, $firstName, $lastName]) {
                $connection->executeStatement(
                    'UPDATE person SET login_username = ?, unique_identifier = ?, first_name = ?, last_name = ?, email_address = ? WHERE login_username = ?',
                    [$username, $username, $firstName, $lastName, $username . '@mhernandez.example', $old]
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
            $connection->executeStatement(
                'UPDATE edu_report_template SET description = ? WHERE organization_id = (SELECT id FROM organization WHERE code = ?)',
                ['Papel con membrete del centro', self::ORGANIZATION_CODE]
            );
            $this->addSurveys($connection);
            $this->addTeaching($connection);
            // calificación numérica de cada nivel de desempeño (en centésimas): Insuficiente 3, Suficiente 6, Notable 8
            $connection->executeStatement(
                "UPDATE edu_performance_scale_value SET numeric_grade = CASE description WHEN 'Insuficiente' THEN 300 WHEN 'Suficiente' THEN 600 ELSE 800 END"
            );
            // el texto de las reuniones es plano (el formulario no tiene editor): sin etiquetas HTML
            $connection->executeStatement(
                'UPDATE wlt_meeting SET detail = ?',
                ['Reunión de seguimiento del proyecto: revisión del calendario de prácticas y de las actividades realizadas hasta la fecha.']
            );
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Una docente que solo da clase en 2º DAM A (Proyecto intermodular) y no tiene ningún otro cargo ni tutoría: es
     * el perfil de «profesorado» de FP dual y de la FFE, que evalúa al alumnado del grupo (usuario «jcortes»)
     */
    private function addTeaching(Connection $connection): void
    {
        $connection->executeStatement(
            'INSERT INTO edu_teaching (teacher_id, group_id, subject_id)
             SELECT t.id, g.id, s.id
             FROM edu_teacher t
             JOIN person p ON p.id = t.person_id AND p.login_username = ?
             JOIN edu_group g ON g.name = ? AND g.grade_id IN (SELECT gr.id FROM edu_grade gr JOIN edu_training tr ON tr.id = gr.training_id WHERE tr.academic_year_id = t.academic_year_id)
             JOIN edu_subject s ON s.name = ? AND s.grade_id = g.grade_id',
            ['jcortes', '2º DAM A', 'Proyecto intermodular']
        );
        if ($connection->fetchOne('SELECT COUNT(*) FROM edu_teaching te JOIN edu_teacher t ON t.id = te.teacher_id JOIN person p ON p.id = t.person_id WHERE p.login_username = ?', ['jcortes']) === '0') {
            throw new \RuntimeException('No se ha podido asignar la docencia de «jcortes»: ha cambiado el reparto de nombres de los datos de relleno.');
        }
    }

    /**
     * Encuestas del centro (capítulo «Encuestas y plantillas de informe»): una de satisfacción del alumnado, con
     * todos los tipos de pregunta, y otra, más corta, para las empresas. Sin respuestas, así que se pueden editar.
     */
    private function addSurveys(Connection $connection): void
    {
        $organization = (int) $connection->fetchOne('SELECT id FROM organization WHERE code = ?', [self::ORGANIZATION_CODE]);

        $surveys = [
            'Satisfacción del alumnado con las prácticas' => [
                ['La empresa me recibió y me explicó cómo se trabaja', 'range_1_5', true],
                ['Las tareas que he realizado se corresponden con mi ciclo formativo', 'range_1_5', true],
                ['He recibido el apoyo de mi tutor o tutora laboral', 'range_1_5', true],
                ['Valoración global de las prácticas (0 a 10)', 'range_0_10', true],
                ['Lo que más me ha gustado', 'text', false],
                ['Comentarios y sugerencias para mejorar', 'textarea', false],
            ],
            'Satisfacción de la empresa con el alumnado' => [
                ['El alumno o la alumna se ha integrado en el equipo', 'range_1_5', true],
                ['Su preparación técnica era adecuada para las tareas encomendadas', 'range_1_5', true],
                ['Observaciones', 'textarea', false],
            ],
            'Satisfacción de la tutoría docente con las prácticas' => [
                ['La empresa ha colaborado en el seguimiento del alumnado', 'range_1_5', true],
                ['La comunicación con la tutoría laboral ha sido fluida', 'range_1_5', true],
                ['Propuestas de mejora para la próxima convocatoria', 'textarea', false],
            ],
        ];

        $ids = [];
        foreach ($surveys as $title => $questions) {
            $connection->insert('survey', ['organization_id' => $organization, 'title' => $title]);
            $survey = (int) $connection->lastInsertId();
            $ids[$title] = $survey;
            foreach ($questions as $order => [$description, $type, $mandatory]) {
                $connection->insert('survey_question', [
                    'survey_id' => $survey,
                    'description' => $description,
                    'type' => $type,
                    'mandatory' => $mandatory ? 1 : 0,
                    'order_nr' => $order + 1,
                ]);
            }
        }

        // la convocatoria de FCT del centro usa las tres encuestas
        $connection->executeStatement(
            'UPDATE wpt_shift SET student_survey_id = ?, company_survey_id = ?, educational_tutor_survey_id = ?
             WHERE subject_id IN (
                 SELECT s.id FROM edu_subject s
                 JOIN edu_grade g ON g.id = s.grade_id JOIN edu_training t ON t.id = g.training_id
                 JOIN edu_academic_year ay ON ay.id = t.academic_year_id WHERE ay.organization_id = ?
             )',
            [
                $ids['Satisfacción del alumnado con las prácticas'],
                $ids['Satisfacción de la empresa con el alumnado'],
                $ids['Satisfacción de la tutoría docente con las prácticas'],
                $organization,
            ]
        );
    }
}
