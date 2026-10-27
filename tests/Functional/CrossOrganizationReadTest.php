<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Prueba de intrusión de lectura entre organizaciones.
 *
 * Un administrador local de la organización B pide todas las pantallas (GET) con identificadores, probando
 * todos los identificadores posibles, y se comprueba que ninguna respuesta correcta contiene datos de la
 * organización A. En las fixtures, el nombre "Alfa" solo aparece en datos de esa organización.
 *
 * @group slow
 */
class CrossOrganizationReadTest extends AppWebTestCase
{
    use ResolvesUrlParametersTrait;

    /** Identificadores candidatos para los parámetros que no se saben resolver */
    private const MAX_ID = 16;

    /** Máximo de combinaciones por ruta (para no multiplicar las rutas con varios identificadores) */
    private const MAX_COMBINATIONS = 300;

    /** Rutas de autenticación y de restablecimiento de contraseña: no tienen datos de organización */
    private const EXCLUDED_PREFIXES = ['/_', '/salir', '/entrar', '/comprobar', '/restablecer', '/forzar', '/publico'];
    private const EXCLUDED_VARIABLES = ['year', 'week', 'userId', 'token'];

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return list<array{string, list<string>}> nombre de la ruta y variables de la URL (sin la página)
     */
    private function readRoutes(): array
    {
        $routes = [];
        foreach (static::getContainer()->get('router')->getRouteCollection() as $name => $route) {
            $methods = $route->getMethods();
            if (($methods !== [] && !in_array('GET', $methods, true)) || $methods === [] && false) {
                continue;
            }
            foreach (self::EXCLUDED_PREFIXES as $prefix) {
                if (str_starts_with($route->getPath(), $prefix)) {
                    continue 2;
                }
            }
            $variables = array_values(array_diff($route->compile()->getVariables(), ['page']));
            if ($variables === [] || array_intersect($variables, self::EXCLUDED_VARIABLES) !== []) {
                continue;
            }
            $routes[] = [$name, $variables];
        }
        sort($routes);

        return $routes;
    }

    private static function containsOrganizationAData(string $content): bool
    {
        return preg_match('/\bAlfa\b|\balfa\./i', $content) === 1;
    }

    public function testOrganizationBAdministratorCannotReadOrganizationAData(): void
    {
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $router = static::getContainer()->get('router');

        $tested = 0;
        $requests = 0;
        $successful = 0;
        $leaks = [];
        foreach ($this->readRoutes() as [$name, $variables]) {
            $combinations = $this->urlParameters($variables, DemoData::ORG_B_CODE, self::MAX_ID);
            if ($combinations === [] || count($combinations) > self::MAX_COMBINATIONS) {
                continue;
            }
            $tested++;
            foreach ($combinations as $parameters) {
                $this->client->request('GET', $router->generate($name, $parameters));
                $requests++;
                $this->em->clear();
                if ($this->client->getResponse()->getStatusCode() !== 200) {
                    continue;
                }
                $successful++;
                if (self::containsOrganizationAData((string) $this->client->getInternalResponse()->getContent())) {
                    $leaks[] = sprintf('%s %s', $name, json_encode($parameters));
                }
            }
        }

        self::assertGreaterThan(100, $tested, 'Se han probado muy pocas rutas');
        // control: el ataque obtiene respuestas correctas con datos de su propia organización
        self::assertGreaterThan(50, $successful, 'Casi no hay respuestas correctas: la prueba no es concluyente');
        self::assertSame([], $leaks, "Pantallas que muestran datos de la otra organización ($requests peticiones):\n" . implode("\n", $leaks));
    }

    /**
     * Control positivo: la detección de datos de la organización A funciona cuando se accede con un usuario de A
     */
    public function testDetectionWorksForOrganizationAUsers(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('GET', static::getContainer()->get('router')->generate(
            'organization_teacher_list',
            ['academicYear' => $this->getOrganization()->getCurrentAcademicYear()->getId()]
        ));

        self::assertResponseIsSuccessful();
        self::assertTrue(self::containsOrganizationAData((string) $this->client->getInternalResponse()->getContent()));
    }
}
