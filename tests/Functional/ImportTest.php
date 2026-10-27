<?php

namespace App\Tests\Functional;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\Person;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Importaciones de datos desde ficheros (CSV de Séneca y calendario iCal): solo quien administra el centro puede
 * usarlas y un fichero mal formado no debe provocar un error del servidor ni dejar datos a medias.
 */
class ImportTest extends AppWebTestCase
{
    private const ICAL = 'ical';

    /**
     * Cabeceras de cada importación; las de iCal no tienen columnas
     */
    private const IMPORTS = [
        'teacher' => ['organization_import_teacher_form', ['Usuario IdEA', 'DNI/Pasaporte', 'Empleado/a']],
        'department' => ['organization_import_department_form', ['Descripción', 'Jefe de departamento']],
        'group' => ['organization_import_group_form', ['Unidad', 'Curso', 'Tutor/a']],
        'student' => ['organization_import_student_form', [
            'Alumno/a', 'Estado Matrícula', 'Unidad', 'Nº Id. Escolar', 'DNI/Pasaporte', 'Nombre', 'Primer apellido',
            'Segundo apellido', 'Sexo',
        ]],
        'student_login' => ['organization_import_student_login_form', ['Nombre', 'Usuario IdEA', 'Activo en Séneca']],
        'subject' => ['organization_import_subject_form', ['Materia', 'Unidad', 'Curso', 'Profesor/a']],
        'criteria' => ['organization_import_criteria_form', [
            'Módulo Profesional', 'Resultado de Aprendizaje', 'Criterios de Evaluación',
        ]],
        'itp_activity' => ['in_company_training_phase_activity_import_form', [
            'Actividad', 'Módulo', 'Resultado de aprendizaje asociado', 'Criterio de evaluación',
        ]],
        'non_working_day' => ['organization_import_non_working_day_form', self::ICAL],
    ];

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function url(string $import): string
    {
        $parameters = [];
        if ($import === 'itp_activity') {
            $programGrade = $this->em()->getRepository(ProgramGrade::class)->createQueryBuilder('pg')
                ->join('pg.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')->join('ay.organization', 'o')
                ->where('o.code = :code')->setParameter('code', DemoData::ORG_A_CODE)
                ->setMaxResults(1)->getQuery()->getSingleResult();
            $parameters['programGrade'] = $programGrade->getId();
        }

        return static::getContainer()->get('router')->generate(self::IMPORTS[$import][0], $parameters);
    }

    /**
     * Rellena el formulario de importación con el fichero indicado y lo envía
     */
    private function upload(string $import, string $content, string $extension = 'csv'): Crawler
    {
        $crawler = $this->client->request('GET', $this->url($import));
        self::assertResponseIsSuccessful();

        $path = tempnam(sys_get_temp_dir(), 'import') . '.' . $extension;
        file_put_contents($path, $content);
        try {
            $form = $crawler->filter('input[type=file]')->closest('form')->form();
            foreach (array_keys($form->all()) as $name) {
                if (str_ends_with($name, '[file]')) {
                    $form[$name]->upload($path);
                }
            }

            return $this->client->submit($form);
        } finally {
            @unlink($path);
        }
    }

    /**
     * Fichero de Séneca: se codifica en ISO-8859-1, como los que genera la plataforma
     */
    private function csv(array $header, array ...$rows): string
    {
        $lines = [implode(',', array_map(fn (string $cell): string => '"' . $cell . '"', $header))];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn (string $cell): string => '"' . $cell . '"', $row));
        }

        return mb_convert_encoding(implode("\n", $lines) . "\n", 'ISO-8859-1', 'UTF-8');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function importProvider(): iterable
    {
        foreach (array_keys(self::IMPORTS) as $import) {
            yield $import => [$import];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unprivilegedImportProvider(): iterable
    {
        foreach (['alfa.profesor', 'alfa.tutor', 'alfa.jefe', 'alfa.economico', 'alfa.alumno1', 'alfa.tutorlaboral'] as $username) {
            foreach (array_keys(self::IMPORTS) as $import) {
                // las actividades de la FFE las gestiona quien gestiona el programa formativo, jefatura incluida
                if ($username === 'alfa.jefe' && $import === 'itp_activity') {
                    continue;
                }
                yield $username . ' / ' . $import => [$username, $import];
            }
        }
    }

    /**
     * @dataProvider importProvider
     */
    public function testAdministratorSeesEveryImportForm(string $import): void
    {
        $this->login($this->getUser('alfa.' . DemoData::ROLE_LOCAL_ADMIN));

        $this->client->request('GET', $this->url($import));

        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider unprivilegedImportProvider
     */
    public function testOnlyAdministratorsCanImport(string $username, string $import): void
    {
        $url = $this->url($import);
        $teachers = $this->em()->getRepository(Teacher::class)->count([]);
        $this->login($this->getUser($username));

        $this->client->request('GET', $url);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $url);
        self::assertResponseStatusCodeSame(403);
        self::assertSame($teachers, $this->em()->getRepository(Teacher::class)->count([]));
    }

    /**
     * @return iterable<string, array{string, string}> tipo de importación y contenido del fichero
     */
    public static function hostileCsvProvider(): iterable
    {
        foreach (array_keys(self::IMPORTS) as $import) {
            if (self::IMPORTS[$import][1] === self::ICAL) {
                continue;
            }
            $header = self::IMPORTS[$import][1];
            $line = fn (string $cell): string => implode(',', array_fill(0, count($header), $cell));
            $head = implode(',', array_map(fn (string $cell): string => '"' . $cell . '"', $header)) . "\n";

            yield $import . ': vacío' => [$import, ''];
            yield $import . ': solo cabecera' => [$import, $head];
            yield $import . ': cabecera equivocada' => [$import, "a,b,c\n1,2,3\n"];
            yield $import . ': fila corta' => [$import, $head . "uno\n"];
            yield $import . ': fila vacía' => [$import, $head . $line('') . "\n"];
            yield $import . ': líneas en blanco' => [$import, $head . "\n\n\n"];
            yield $import . ': sin coma ni apellidos' => [$import, $head . $line('SinComa') . "\n"];
            yield $import . ': código en las celdas' => [$import, $head . $line('<script>alert(1)</script>') . "\n"];
            yield $import . ': valores larguísimos' => [$import, $head . $line(str_repeat('A', 5000)) . "\n"];
            yield $import . ': comillas sin cerrar' => [$import, $head . "\"abierta,x\n"];
            yield $import . ': separador punto y coma' => [$import, str_replace(',', ';', $head . $line('x') . "\n")];
            yield $import . ': binario' => [$import, random_bytes(4096)];
        }
    }

    /**
     * @dataProvider hostileCsvProvider
     */
    public function testMalformedCsvFilesDoNotBreakTheImport(string $import, string $content): void
    {
        $this->login($this->getUser('alfa.' . DemoData::ROLE_LOCAL_ADMIN));

        $crawler = $this->upload($import, $content);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode(), 'La importación ha provocado un error del servidor');
        self::assertStringNotContainsString('<script>alert(1)</script>', (string) $this->client->getResponse()->getContent());
        self::assertNotNull($crawler);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostileCalendarProvider(): iterable
    {
        yield 'vacío' => [''];
        yield 'sin eventos' => ["BEGIN:VCALENDAR\nVERSION:2.0\nEND:VCALENDAR\n"];
        yield 'fecha inválida' => ["BEGIN:VCALENDAR\nBEGIN:VEVENT\nDTSTART:no-es-una-fecha\nSUMMARY:Festivo\nEND:VEVENT\nEND:VCALENDAR\n"];
        yield 'evento sin fecha' => ["BEGIN:VCALENDAR\nBEGIN:VEVENT\nSUMMARY:Festivo\nEND:VEVENT\nEND:VCALENDAR\n"];
        yield 'código en la descripción' => ["BEGIN:VCALENDAR\nBEGIN:VEVENT\nDTSTART:20991231\nSUMMARY:<script>alert(1)</script>\nEND:VEVENT\nEND:VCALENDAR\n"];
        yield 'binario' => [random_bytes(4096)];
        yield 'texto cualquiera' => ["esto no es un calendario\n"];
    }

    /**
     * @dataProvider hostileCalendarProvider
     */
    public function testMalformedCalendarFilesDoNotBreakTheImport(string $content): void
    {
        $this->login($this->getUser('alfa.' . DemoData::ROLE_LOCAL_ADMIN));

        $this->upload('non_working_day', $content, 'ics');

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode(), 'La importación ha provocado un error del servidor');
        self::assertStringNotContainsString('<script>alert(1)</script>', (string) $this->client->getResponse()->getContent());
    }

    public function testTeacherImportCreatesTeachersOnceEvenIfRepeated(): void
    {
        $this->login($this->getUser('alfa.' . DemoData::ROLE_LOCAL_ADMIN));
        $before = $this->em()->getRepository(Teacher::class)->count([]);
        $content = $this->csv(
            self::IMPORTS['teacher'][1],
            ['ximportado1', '11111111A', 'Muñoz Peña, Ángela'],
            ['ximportado2', '22222222B', 'Ruiz Gil, Pedro'],
            ['ximportado1', '11111111A', 'Muñoz Peña, Ángela'],
        );

        $this->upload('teacher', $content);
        self::assertResponseIsSuccessful();
        self::assertSame($before + 2, $this->em()->getRepository(Teacher::class)->count([]));
        $person = $this->em()->getRepository(Person::class)->findOneBy(['loginUsername' => 'ximportado1']);
        self::assertSame('Ángela', $person->getFirstName());
        self::assertSame('Muñoz Peña', $person->getLastName());

        // volver a importar el mismo fichero no duplica nada
        $this->upload('teacher', $content);
        self::assertResponseIsSuccessful();
        self::assertSame($before + 2, $this->em()->getRepository(Teacher::class)->count([]));
        self::assertCount(1, $this->em()->getRepository(Person::class)->findBy(['loginUsername' => 'ximportado1']));
    }

    public function testTeacherImportCannotTargetTheAcademicYearOfAnotherOrganization(): void
    {
        $this->login($this->getUser('alfa.' . DemoData::ROLE_LOCAL_ADMIN));
        $foreignYear = $this->em()->getRepository(AcademicYear::class)->createQueryBuilder('ay')
            ->join('ay.organization', 'o')->where('o.code = :code')->setParameter('code', DemoData::ORG_B_CODE)
            ->setMaxResults(1)->getQuery()->getSingleResult();
        $before = $this->em()->getRepository(Teacher::class)->count([]);

        $crawler = $this->client->request('GET', $this->url('teacher'));
        $path = tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($path, $this->csv(self::IMPORTS['teacher'][1], ['xajeno', '33333333C', 'Gil Gil, Ana']));
        try {
            $form = $crawler->filter('input[type=file]')->closest('form')->form();
            $values = $form->getPhpValues();
            $values['teacher_import']['academicYear'] = (string) $foreignYear->getId();
            $files = $form->getPhpFiles();
            $files['teacher_import']['file'] = ['name' => 'a.csv', 'type' => 'text/csv', 'tmp_name' => $path, 'error' => 0, 'size' => filesize($path)];
            $this->client->request('POST', $this->url('teacher'), $values, $files);
        } finally {
            @unlink($path);
        }

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($before, $this->em()->getRepository(Teacher::class)->count([]), 'Se ha importado profesorado en otro centro');
        self::assertNull($this->em()->getRepository(Person::class)->findOneBy(['loginUsername' => 'xajeno']));
    }
}
