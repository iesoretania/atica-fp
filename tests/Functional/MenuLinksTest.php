<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;

/**
 * Todo enlace que el menú ofrece a una persona debe poder abrirse: una entrada de menú que acaba en un 403
 * es un enlace roto para quien la ve.
 */
class MenuLinksTest extends AppWebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function userProvider(): iterable
    {
        foreach ([
            'alfa.admin', 'alfa.economico', 'alfa.jefe', 'alfa.coordinador', 'alfa.gestorffe', 'alfa.tutor',
            'alfa.profesor', 'alfa.alumno1', 'alfa.tutorlaboral',
        ] as $username) {
            yield $username => [$username];
        }
    }

    /**
     * @dataProvider userProvider
     */
    public function testEveryMenuLinkIsAccessible(string $username): void
    {
        $this->login($this->getUser($username));

        $links = $this->menuLinks('/');
        // las pantallas de inicio de cada apartado (las tarjetas de «Gestión del centro», de cada módulo...)
        foreach ($links as $link) {
            foreach ($this->menuLinks($link) as $child) {
                $links[$child] = $child;
            }
        }

        self::assertNotEmpty($links);
        $denied = [];
        foreach ($links as $link) {
            $this->client->request('GET', $link);
            $status = $this->client->getResponse()->getStatusCode();
            if ($status === 403) {
                $denied[] = $link;
            }
        }

        self::assertSame([], $denied, "El menú de $username ofrece enlaces a los que no tiene acceso");
    }

    /**
     * El responsable económico puede gestionar los gastos de desplazamiento de los tres módulos, así que los tres
     * deben ofrecerle el camino hasta ellos (y solo ese)
     */
    public function testFinancialManagerReachesTheExpensesOfEveryModule(): void
    {
        $this->login($this->getUser('alfa.economico'));

        foreach (['/fct' => '/fct/desplazamiento/', '/dual' => '/dual/desplazamiento/', '/formacion' => '/formacion/desplazamiento/'] as $home => $expenses) {
            $links = $this->menuLinks($home);

            self::assertNotEmpty($links, "El responsable económico no tiene acceso a $home");
            self::assertNotEmpty(array_filter($links, static fn(string $link): bool => str_starts_with($link, $expenses)), "Falta el enlace a los gastos en $home");
        }
    }

    /**
     * En la FFE el responsable económico entra solo para los gastos: no se le ofrecen seguimiento, valoración,
     * evaluación ni encuestas, que para él estarían vacíos (o, antes, fallaban)
     */
    public function testFinancialManagerIsOnlyOfferedTheExpensesInFfe(): void
    {
        $this->login($this->getUser('alfa.economico'));

        $links = $this->menuLinks('/formacion');

        self::assertSame(['/formacion', '/formacion/desplazamiento/resumen'], array_values(array_filter(
            $links,
            static fn(string $link): bool => str_starts_with($link, '/formacion')
        )));
    }

    /**
     * El alumnado de FFE solo sigue su estancia y contesta la encuesta: ni valoración ni evaluación de grupo
     */
    public function testFfeStudentIsNotOfferedTheGradingScreens(): void
    {
        $this->login($this->getUser('alfa.alumno7'));

        $links = $this->menuLinks('/formacion');

        self::assertNotEmpty(array_filter($links, static fn(string $link): bool => str_starts_with($link, '/formacion/seguimiento')));
        self::assertSame([], array_filter($links, static fn(string $link): bool => str_starts_with($link, '/formacion/valoracion')));
    }

    /**
     * El profesorado que solo da clase en FP dual no participa en ninguna encuesta: no debe ver la entrada
     */
    public function testTeachingOnlyStaffIsNotOfferedAnEmptySurveySection(): void
    {
        $db = static::getContainer()->get('doctrine')->getConnection();
        $db->executeStatement(
            'INSERT INTO edu_teaching (teacher_id, group_id, subject_id)
             SELECT t.id, se.group_id, (SELECT s.id FROM edu_subject s JOIN edu_group g ON g.grade_id = s.grade_id WHERE g.id = se.group_id LIMIT 1)
             FROM edu_teacher t
             JOIN person p ON p.id = t.person_id AND p.login_username = ?
             JOIN edu_student_enrollment se ON se.person_id = (SELECT id FROM person WHERE login_username = ?)
             WHERE t.academic_year_id = ? AND NOT EXISTS (SELECT 1 FROM edu_teaching x WHERE x.teacher_id = t.id)',
            ['alfa.director', 'alfa.alumno5', $this->getOrganization()->getCurrentAcademicYear()->getId()]
        );
        $this->login($this->getUser('alfa.director'));

        $links = $this->menuLinks('/dual');

        self::assertNotEmpty($links);
        self::assertSame([], array_filter($links, static fn(string $link): bool => str_starts_with($link, '/dual/encuesta')));
    }

    /**
     * @return array<string, string> enlaces internos del menú y de las tarjetas de la página, por ruta
     */
    private function menuLinks(string $path): array
    {
        $crawler = $this->client->request('GET', $path);
        if ($this->client->getResponse()->getStatusCode() >= 400) {
            return [];
        }

        $links = [];
        foreach ($crawler->filter('nav a[href], main a.card[href], main .card a[href], main .menu a[href]') as $anchor) {
            $href = $anchor->getAttribute('href');
            if ($href === '' || $href === '#' || !str_starts_with($href, '/') || str_contains($href, 'salir') || str_contains($href, '_switch_user')) {
                continue;
            }
            $links[$href] = $href;
        }

        return $links;
    }
}
