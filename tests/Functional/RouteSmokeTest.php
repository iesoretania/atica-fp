<?php

namespace App\Tests\Functional;

/**
 * Recorre todas las rutas GET sin parámetros definidas en la aplicación
 */
class RouteSmokeTest extends AppWebTestCase
{
    // rutas públicas (no exigen sesión) y rutas que cierran la sesión o no son páginas
    private const PUBLIC_PREFIXES = ['/entrar', '/restablecer', '/publico', '/comprobar'];
    private const EXCLUDED = ['/salir', '/_wdt', '/_profiler'];

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function routeProvider(): iterable
    {
        self::bootKernel();
        $routes = self::getContainer()->get('router')->getRouteCollection();
        self::ensureKernelShutdown();

        foreach ($routes as $name => $route) {
            $path = $route->getPath();
            $methods = $route->getMethods();
            if (str_contains($path, '{') || ($methods !== [] && !in_array('GET', $methods, true))) {
                continue;
            }
            foreach (self::EXCLUDED as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    continue 2;
                }
            }
            yield $name => [$name, $path];
        }
    }

    private static function isPublic(string $path): bool
    {
        foreach (self::PUBLIC_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @dataProvider routeProvider
     */
    public function testAnonymousCannotAccess(string $name, string $path): void
    {
        $this->client->request('GET', $path);
        $status = $this->client->getResponse()->getStatusCode();

        if (self::isPublic($path)) {
            self::assertLessThan(500, $status, "La ruta pública $name ($path) devuelve $status");
            return;
        }

        self::assertTrue(
            $this->client->getResponse()->isRedirect(),
            "El anónimo no debe acceder a $name ($path), pero obtiene HTTP $status"
        );
        self::assertStringNotContainsString(
            'Cache-Control: public',
            (string) $this->client->getResponse()->headers
        );
    }

    /**
     * @dataProvider routeProvider
     */
    public function testAdminGetsNoServerError(string $name, string $path): void
    {
        if (self::isPublic($path)) {
            $this->markTestSkipped('Ruta pública');
        }
        $this->login($this->getAdmin());
        $this->client->request('GET', $path);
        $status = $this->client->getResponse()->getStatusCode();

        self::assertLessThan(500, $status, "El administrador obtiene HTTP $status en $name ($path)");
        self::assertNotSame(403, $status, "El administrador no debería recibir 403 en $name ($path)");
    }

    /**
     * @dataProvider routeProvider
     */
    public function testRegularUserCannotAccessAdminArea(string $name, string $path): void
    {
        if (!str_starts_with($path, '/admin')) {
            $this->markTestSkipped('No es una ruta de administración');
        }
        $this->login($this->getRegularUser());
        $this->client->request('GET', $path);
        $status = $this->client->getResponse()->getStatusCode();

        self::assertContains(
            $status,
            [403, 302],
            "Un usuario sin permisos obtiene HTTP $status en $name ($path)"
        );
    }
}
