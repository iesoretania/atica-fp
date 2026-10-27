<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * Control de acceso global (access_control de config/packages/security.yaml): toda ruta exige haber
 * iniciado sesión salvo las de entrada, cierre de sesión y restablecimiento de contraseña, y la
 * administración global exige ser administrador global, con independencia de las comprobaciones de
 * cada controlador.
 */
class AccessControlTest extends AppWebTestCase
{
    private const PUBLIC_PATTERN = '#^/(entrar|comprobar|salir|restablecer)(/|$)#';

    /**
     * Todas las rutas (con todos sus métodos) y una dirección de ejemplo con "1" en cada variable
     *
     * @return iterable<string, array{string, string, string}> nombre, método y dirección
     */
    public static function routeProvider(): iterable
    {
        self::bootKernel();
        $router = self::getContainer()->get('router');
        $routes = $router->getRouteCollection();
        self::ensureKernelShutdown();

        foreach ($routes as $name => $route) {
            if (str_starts_with($route->getPath(), '/_')) {
                continue;
            }
            $variables = $route->compile()->getVariables();
            try {
                $url = $router->generate($name, array_fill_keys($variables, '1'));
            } catch (InvalidParameterException|MissingMandatoryParametersException) {
                continue;
            }
            $methods = $route->getMethods() ?: ['GET'];
            foreach ($methods as $method) {
                yield "$method $name" => [$name, $method, $url];
            }
        }
    }

    /**
     * @dataProvider routeProvider
     */
    public function testAnonymousUsersAreSentToTheLoginPage(string $name, string $method, string $url): void
    {
        if (preg_match(self::PUBLIC_PATTERN, $url) === 1) {
            $this->markTestSkipped('Ruta pública');
        }

        $this->client->request($method, $url);

        self::assertResponseRedirects(null, 302, "$method $url ($name) debería exigir iniciar sesión");
        self::assertMatchesRegularExpression(
            '#/entrar$#',
            (string) $this->client->getResponse()->headers->get('Location'),
            "$method $url ($name) debería llevar directamente a la pantalla de entrada"
        );
    }

    /**
     * @dataProvider routeProvider
     */
    public function testOnlyGlobalAdministratorsReachTheAdministrationArea(string $name, string $method, string $url): void
    {
        if (!preg_match('#^/admin(/|$)#', $url)) {
            $this->markTestSkipped('No es una ruta de administración');
        }

        // ni siquiera el administrador local de una organización
        foreach ([DemoData::USER_A_LOCAL_ADMIN, DemoData::USER_A_TEACHER] as $username) {
            static::ensureKernelShutdown();
            $this->client = static::createClient();
            $this->login($this->getUser($username));
            $this->client->request($method, $url);

            self::assertResponseStatusCodeSame(403, "$username en $method $url ($name)");
        }
    }

    public function testPublicPagesRemainAccessible(): void
    {
        foreach (['/entrar', '/restablecer'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
        }

        // cerrar sesión sin haberla iniciado no es un error
        $this->client->request('GET', '/salir');
        self::assertResponseRedirects();
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    public function testLoginReturnsToTheRequestedPageAndLogoutProtectsItAgain(): void
    {
        $protectedUrl = static::getContainer()->get('router')->generate('personal_data');
        $ip = '10.' . random_int(1, 250) . '.' . random_int(1, 250) . '.' . random_int(1, 250);

        // 1) sin sesión se redirige a la pantalla de entrada
        $this->client->request('GET', $protectedUrl);
        self::assertResponseRedirects();
        $crawler = $this->client->followRedirect();
        self::assertSame('/entrar', parse_url($this->client->getRequest()->getUri(), PHP_URL_PATH));

        // 2) entrar con credenciales válidas devuelve a la página que se pidió
        $this->client->request('POST', '/comprobar', [
            '_username' => DemoData::USER_A_TEACHER,
            '_password' => DemoData::PASSWORD,
            '_csrf_token' => $crawler->filter('input[name=_csrf_token]')->attr('value'),
        ], [], ['REMOTE_ADDR' => $ip]);
        self::assertResponseRedirects();
        self::assertSame($protectedUrl, parse_url($this->client->getResponse()->headers->get('Location'), PHP_URL_PATH));

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();

        // 3) tras cerrar sesión vuelve a estar protegida
        $this->client->request('GET', '/salir');
        $this->client->request('GET', $protectedUrl);
        self::assertResponseRedirects();
        self::assertStringEndsWith('/entrar', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testSessionCookieIsNotAccessibleFromJavaScript(): void
    {
        $options = static::getContainer()->getParameter('session.storage.options');

        self::assertTrue($options['cookie_httponly'], 'La cookie de sesión debe ser HttpOnly');
        self::assertSame('lax', $options['cookie_samesite']);

        // y la respuesta que la establece lleva el atributo
        $this->client->request('GET', '/entrar');
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === static::getContainer()->getParameter('session_name')) {
                self::assertTrue($cookie->isHttpOnly());
            }
        }
    }

    public function testWrongPasswordDoesNotLogIn(): void
    {
        $ip = '10.' . random_int(1, 250) . '.' . random_int(1, 250) . '.' . random_int(1, 250);
        $crawler = $this->client->request('GET', '/entrar');
        $this->client->request('POST', '/comprobar', [
            '_username' => DemoData::USER_A_TEACHER,
            '_password' => 'contraseña-incorrecta',
            '_csrf_token' => $crawler->filter('input[name=_csrf_token]')->attr('value'),
        ], [], ['REMOTE_ADDR' => $ip]);
        $this->client->followRedirect();

        // sigue sin poder entrar en una página protegida
        $this->client->request('GET', static::getContainer()->get('router')->generate('personal_data'));
        self::assertResponseRedirects();
        self::assertStringEndsWith('/entrar', (string) $this->client->getResponse()->headers->get('Location'));
    }
}
