<?php

namespace App\Tests\Functional;

use App\Entity\Organization;
use App\Listener\OrganizationOwnershipSubscriber;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Prueba de intrusión entre organizaciones sobre las operaciones de borrado.
 *
 * Los identificadores de los elementos a borrar llegan en el cuerpo de la petición (items[]), por lo
 * que no los cubre la comprobación de la URL. Un usuario de una organización intenta borrar con todos
 * los identificadores posibles, usando los parámetros de URL de su propia organización, y se comprueba
 * que no desaparece ninguna fila que pertenezca a otra.
 *
 * @group slow
 */
class CrossOrganizationDeleteTest extends AppWebTestCase
{
    /** Identificadores candidatos para los parámetros de URL "id" y para el contenido de items[] */
    private const MAX_CANDIDATE_ID = 1500;
    private const MAX_URL_ID = 60;

    private EntityManagerInterface $em;

    /** @var array<class-string, array<int, int|null>> organización propietaria de cada fila (null: sin organización) */
    private array $owner = [];

    /** @var array<string, class-string> tabla => clase */
    private array $tables = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return list<array{string, list<string>}> nombre de la ruta y variables de la URL (sin la página)
     */
    private function deleteRoutes(): array
    {
        $routes = [];
        foreach (static::getContainer()->get('router')->getRouteCollection() as $name => $route) {
            if (!in_array('POST', $route->getMethods(), true)
                || !preg_match('/eliminar|operacion|operation|delete/i', $route->getPath() . $name)) {
                continue;
            }
            $routes[] = [$name, array_values(array_diff($route->compile()->getVariables(), ['page']))];
        }
        sort($routes);

        return $routes;
    }

    /**
     * Calcula, antes de borrar nada, a qué organización pertenece cada fila de cada entidad
     */
    private function mapOwners(): void
    {
        $subscriber = new OrganizationOwnershipSubscriber($this->createStub(AuthorizationCheckerInterface::class));
        $finder = new \ReflectionMethod($subscriber, 'findOrganization');

        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $metadata) {
            if ($metadata->isMappedSuperclass || $metadata->isEmbeddedClass || $metadata->getIdentifierFieldNames() !== ['id']) {
                continue;
            }
            $class = $metadata->getName();
            $this->tables[$metadata->getTableName()] = $class;
            foreach ($this->em->getRepository($class)->findAll() as $entity) {
                $organization = $finder->invoke($subscriber, $entity);
                $this->owner[$class][$entity->getId()] = $organization instanceof Organization ? $organization->getId() : null;
            }
            $this->em->clear();
        }
    }

    /**
     * @return array<string, array<int, true>> identificadores existentes por tabla
     */
    private function snapshot(): array
    {
        $connection = $this->em->getConnection();
        $snapshot = [];
        foreach (array_keys($this->tables) as $table) {
            $snapshot[$table] = array_fill_keys(array_map('intval', $connection->fetchFirstColumn("SELECT id FROM `$table`")), true);
        }

        return $snapshot;
    }

    /**
     * Parámetros de URL de una ruta con los datos de la organización indicada
     *
     * @param list<string> $variables
     * @return list<array<string, int>> posibles combinaciones de parámetros
     */
    private function urlParameters(array $variables, string $organizationCode): array
    {
        $organization = $this->getOrganization($organizationCode);
        $connection = $this->em->getConnection();
        $firstId = fn (string $sql): ?int => ($id = $connection->fetchOne($sql, [$organization->getId()])) === false ? null : (int) $id;

        $known = [
            'academicYear' => $organization->getCurrentAcademicYear()->getId(),
            'shift' => $firstId('SELECT s.id FROM wpt_shift s JOIN edu_subject su ON su.id = s.subject_id JOIN edu_grade g ON g.id = su.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
            'project' => $firstId('SELECT id FROM wlt_project WHERE organization_id = ? LIMIT 1'),
            'trainingProgram' => $firstId('SELECT tp.id FROM itp_training_program tp JOIN itp_program_grade pg ON pg.training_program_id = tp.id JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
            'programGrade' => $firstId('SELECT pg.id FROM itp_program_grade pg JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
            'programGroup' => $firstId('SELECT pgr.id FROM itp_program_group pgr JOIN itp_program_grade pg ON pg.id = pgr.program_grade_id JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
            'studentProgram' => $firstId('SELECT sp.id FROM itp_student_program sp JOIN itp_program_group pgr ON pgr.id = sp.program_group_id JOIN itp_program_grade pg ON pg.id = pgr.program_grade_id JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
            'studentProgramWorkcenter' => $firstId('SELECT spw.id FROM itp_student_program_workcenter spw JOIN itp_student_program sp ON sp.id = spw.student_program_id JOIN itp_program_group pgr ON pgr.id = sp.program_group_id JOIN itp_program_grade pg ON pg.id = pgr.program_grade_id JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year a ON a.id = t.academic_year_id WHERE a.organization_id = ? LIMIT 1'),
        ];

        $combinations = [[]];
        foreach ($variables as $variable) {
            $candidates = $variable === 'id' ? range(1, self::MAX_URL_ID) : [$known[$variable] ?? null];
            if ($candidates === [null]) {
                return [];
            }
            $next = [];
            foreach ($combinations as $combination) {
                foreach ($candidates as $candidate) {
                    $next[] = $combination + [$variable => $candidate];
                }
            }
            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * Intenta borrar con todos los identificadores en todas las rutas de borrado.
     *
     * @return array{int, list<string>} rutas probadas y filas desaparecidas que no eran de la organización
     */
    private function attack(string $username, string $ownCode, ?int $victimOrganizationId): array
    {
        $this->login($this->getUser($username), true, $ownCode);
        $router = static::getContainer()->get('router');
        $items = range(1, self::MAX_CANDIDATE_ID);
        $tested = 0;
        $violations = [];
        $deletedAnywhere = 0;

        foreach ($this->deleteRoutes() as [$name, $variables]) {
            $combinations = $this->urlParameters($variables, $ownCode);
            if ($combinations === []) {
                continue;
            }
            $before = $this->snapshot();
            foreach ($combinations as $parameters) {
                $this->client->request('POST', $router->generate($name, $parameters), ['items' => $items, 'confirm' => 'ok']);
                $this->em->clear();
            }
            $tested++;
            $after = $this->snapshot();

            foreach ($before as $table => $ids) {
                foreach (array_keys(array_diff_key($ids, $after[$table])) as $id) {
                    $deletedAnywhere++;
                    $owner = $this->owner[$this->tables[$table]][$id] ?? null;
                    if ($victimOrganizationId !== null && $owner === $victimOrganizationId) {
                        $violations[] = sprintf('%s: se ha borrado %s #%d de la otra organización', $name, $this->tables[$table], $id);
                    }
                }
            }
        }

        return [$tested, $violations, $deletedAnywhere];
    }

    public function testOrganizationBUserCannotDeleteOrganizationAData(): void
    {
        $this->mapOwners();
        $organizationA = $this->getOrganization(DemoData::ORG_A_CODE)->getId();

        [$tested, $violations, $deleted] = $this->attack('beta.admin', DemoData::ORG_B_CODE, $organizationA);

        self::assertGreaterThan(30, $tested, 'Se han probado muy pocas rutas de borrado');
        self::assertSame([], $violations, implode("\n", $violations));
        // control: el ataque sí borra datos de la propia organización, por lo que la prueba es concluyente
        self::assertGreaterThan(0, $deleted, 'El ataque no ha borrado nada: la prueba no es concluyente');
    }
}
