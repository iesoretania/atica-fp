<?php

namespace App\Tests\Functional;

class LoginTest extends AppWebTestCase
{
    public function testLoginFormHasCsrfToken(): void
    {
        $crawler = $this->client->request('GET', '/entrar');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('input[name=_csrf_token]'));
        self::assertNotSame('', $crawler->filter('input[name=_csrf_token]')->attr('value'));
    }

    public function testLoginWithoutCsrfTokenIsRejected(): void
    {
        $this->client->request('GET', '/entrar');
        $this->client->request(
            'POST',
            '/comprobar',
            ['_username' => 'admin', '_password' => 'admin'],
            [],
            ['REMOTE_ADDR' => '10.20.1.1']
        );
        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert-danger', 'Token CSRF no válido');
    }

    public function testUnknownUserAndWrongPasswordGiveSameMessageAndAreThrottled(): void
    {
        $crawler = $this->client->request('GET', '/entrar');
        $token = $crawler->filter('input[name=_csrf_token]')->attr('value');
        // IP única por ejecución para no depender del estado del limitador
        $ip = '10.30.' . random_int(1, 250) . '.' . random_int(1, 250);

        $messages = [];
        foreach (['nadie-existe', 'admin'] as $username) {
            $this->client->request('POST', '/comprobar', [
                '_username' => $username,
                '_password' => 'contraseña-incorrecta',
                '_csrf_token' => $token
            ], [], ['REMOTE_ADDR' => $ip]);
            $crawler = $this->client->followRedirect();
            $messages[] = trim($crawler->filter('.alert-danger')->text());
        }

        self::assertSame($messages[0], $messages[1], 'No debe distinguirse entre usuario inexistente y contraseña incorrecta');
        self::assertStringContainsString('Credenciales no válidas', $messages[0]);

        // seguir intentándolo hasta superar el límite
        $last = '';
        for ($i = 0; $i < 6; $i++) {
            $this->client->request('POST', '/comprobar', [
                '_username' => 'admin',
                '_password' => 'otra-incorrecta',
                '_csrf_token' => $token
            ], [], ['REMOTE_ADDR' => $ip]);
            $crawler = $this->client->followRedirect();
            $last = trim($crawler->filter('.alert-danger')->text());
        }
        self::assertStringContainsString('Demasiados intentos', $last);
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $this->client->followRedirects(false);
        $this->client->request('GET', '/admin/usuarios/listar');

        // la aplicación puede pasar antes por la selección de organización
        for ($i = 0; $i < 3 && $this->client->getResponse()->isRedirect(); $i++) {
            $location = $this->client->getResponse()->headers->get('Location');
            if (str_contains($location, '/entrar')) {
                break;
            }
            $this->client->request('GET', $location);
        }

        self::assertStringContainsString('/entrar', $this->client->getResponse()->headers->get('Location') ?? '');
    }
}
