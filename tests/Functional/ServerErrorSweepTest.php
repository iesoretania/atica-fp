<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Barrido de errores del servidor: el administrador local de una organización pide todas las pantallas (GET)
 * con identificadores de sus propios datos, incluidas las exportaciones a PDF, CSV y calendario, y ninguna
 * debe devolver un error 5xx.
 *
 * @group slow
 */
class ServerErrorSweepTest extends AppWebTestCase
{
    use ResolvesUrlParametersTrait;

    private const MAX_ID = 16;
    private const MAX_COMBINATIONS = 300;
    private const EXCLUDED_PREFIXES = ['/_', '/salir', '/entrar', '/comprobar', '/restablecer', '/forzar', '/publico', '/admin/usuarios/suplantar'];
    private const EXCLUDED_VARIABLES = ['token'];

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return list<array{string, list<string>}>
     */
    private function readRoutes(): array
    {
        $routes = [];
        foreach (static::getContainer()->get('router')->getRouteCollection() as $name => $route) {
            $methods = $route->getMethods();
            if ($methods !== [] && !in_array('GET', $methods, true)) {
                continue;
            }
            foreach (self::EXCLUDED_PREFIXES as $prefix) {
                if (str_starts_with($route->getPath(), $prefix)) {
                    continue 2;
                }
            }
            $variables = array_values(array_diff($route->compile()->getVariables(), ['page']));
            if (array_intersect($variables, self::EXCLUDED_VARIABLES) !== []) {
                continue;
            }
            $routes[] = [$name, $variables];
        }
        sort($routes);

        return $routes;
    }

    /**
     * @dataProvider organizationProvider
     */
    public function testNoRouteReturnsAServerError(string $username, string $organizationCode, int $minDownloads, int $minSuccessful): void
    {
        $this->login($this->getUser($username), true, $organizationCode);
        $router = static::getContainer()->get('router');

        $tested = 0;
        $successful = 0;
        $downloads = 0;
        $errors = [];
        foreach ($this->readRoutes() as [$name, $variables]) {
            $combinations = $this->urlParameters($variables, $organizationCode, self::MAX_ID);
            if ($combinations === [] || count($combinations) > self::MAX_COMBINATIONS) {
                continue;
            }
            $tested++;
            foreach ($combinations as $parameters) {
                $this->client->request('GET', $router->generate($name, $parameters));
                $response = $this->client->getResponse();
                $status = $response->getStatusCode();
                if ($status === 200) {
                    $successful++;
                    if (!str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
                        $downloads++;
                    }
                }
                if ($status >= 500) {
                    $errors[$name . ' ' . json_encode($parameters)] = $status;
                }
                $this->em->clear();
            }
        }

        self::assertGreaterThan(150, $tested, 'Se han probado muy pocas rutas');
        self::assertGreaterThanOrEqual($minSuccessful, $successful,'Casi no hay respuestas correctas: la prueba no es concluyente');
        self::assertGreaterThanOrEqual($minDownloads, $downloads, 'Se han descargado pocos ficheros: las exportaciones no se han probado');
        self::assertSame([], $errors, "Rutas con error del servidor:\n" . implode("\n", array_map(
            static fn(string $key, int $status): string => "$status $key",
            array_keys($errors),
            $errors
        )));
    }

    /**
     * @return iterable<string, array{string, string, int, int}> usuario, organización, descargas y respuestas correctas mínimas
     */
    public static function organizationProvider(): iterable
    {
        yield 'administrador local' => [DemoData::USER_A_LOCAL_ADMIN, DemoData::ORG_A_CODE, 6, 100];
        yield 'tutor de grupo' => ['alfa.tutor', DemoData::ORG_A_CODE, 1, 100];
        yield 'coordinador de FP dual' => ['alfa.coordinador', DemoData::ORG_A_CODE, 0, 100];
        yield 'gestor de la FFE' => ['alfa.gestorffe', DemoData::ORG_A_CODE, 0, 50];
        yield 'tutor laboral' => ['alfa.tutorlaboral', DemoData::ORG_A_CODE, 0, 50];
        yield 'alumno de FCT' => ['alfa.alumno1', DemoData::ORG_A_CODE, 0, 30];
        yield 'alumno de FP dual' => ['alfa.alumno5', DemoData::ORG_A_CODE, 0, 30];
        yield 'alumno de FFE' => ['alfa.alumno7', DemoData::ORG_A_CODE, 0, 30];
    }
}
