<?php

namespace App\Tests\Functional;

use App\Entity\Person;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * Ningún formulario de la aplicación debe provocar un error del servidor (HTTP 500) por recibir datos
 * vacíos, muy largos o con caracteres especiales: el atributo "required" del navegador no valida nada en
 * el servidor, y un TypeError no capturado se convierte en un error 500.
 *
 * @group slow
 */
class FormRobustnessTest extends AppWebTestCase
{
    use ResolvesUrlParametersTrait;

    private const MAX_ID = 6;
    private const MAX_COMBINATIONS = 40;
    private const EXCLUDED_PREFIXES = ['/_', '/salir', '/entrar', '/comprobar', '/restablecer', '/forzar', '/publico'];

    /** Variantes con las que se rellenan los campos de texto */
    private const VARIANTS = [
        'vacío' => '',
        'espacios' => '   ',
        'muy largo' => null, // se genera en tiempo de ejecución (más de 255 caracteres)
        'especiales' => "<script>alert('x')</script> ' \" \\ % _ ; -- ñ 😀",
    ];

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * Administrador global creado solo para esta prueba: así los datos basura enviados a los formularios
     * de usuarios no pueden dañar la cuenta "admin" que usan el resto de tests
     */
    private function createDisposableAdministrator(): int
    {
        $person = new Person();
        $person
            ->setLoginUsername('admin.desechable')
            ->setFirstName('Administrador')
            ->setLastName('Desechable')
            ->setEmailAddress('admin.desechable@example.com')
            ->setEnabled(true)
            ->setGlobalAdministrator(true)
            ->setForcePasswordChange(false)
            ->setExternalCheck(false)
            ->setAllowExternalCheck(false);
        $person->setPassword(static::getContainer()->get('security.user_password_hasher')->hashPassword($person, DemoData::PASSWORD));
        $this->em->persist($person);
        $this->em->flush();
        $id = $person->getId();
        $this->em->clear();

        return $id;
    }

    /**
     * Rutas con formulario (GET y POST) que no son confirmaciones de borrado, que se prueban aparte
     *
     * @return list<array{string, list<string>}>
     */
    private function formRoutes(): array
    {
        $routes = [];
        foreach (static::getContainer()->get('router')->getRouteCollection() as $name => $route) {
            $methods = $route->getMethods();
            if (!in_array('GET', $methods, true) || !in_array('POST', $methods, true)) {
                continue;
            }
            foreach (self::EXCLUDED_PREFIXES as $prefix) {
                if (str_starts_with($route->getPath(), $prefix)) {
                    continue 2;
                }
            }
            if (preg_match('/eliminar|operacion|operation|delete/i', $route->getPath() . $name)) {
                continue;
            }
            $routes[] = [$name, array_values(array_diff($route->compile()->getVariables(), ['page', 'year', 'week', 'userId', 'token']))];
        }
        sort($routes);

        return $routes;
    }

    /**
     * @return iterable<Form>
     */
    private function formsOf(Crawler $crawler): iterable
    {
        foreach ($crawler->filter('form') as $node) {
            $formNode = new Crawler($node, $crawler->getUri());
            // los formularios de subida de ficheros se prueban con ficheros, no con texto
            if ($formNode->filter('input[type=file]')->count() > 0) {
                continue;
            }
            $button = $formNode->filter('button[type=submit], button:not([type]), input[type=submit]')->first();
            if ($button->count() === 0) {
                continue;
            }
            yield $button->form();
        }
    }

    private function fillTextFields(Form $form, string $value): int
    {
        $filled = 0;
        $crawler = new Crawler($form->getFormNode(), $form->getUri());
        $crawler->filter('input[type=text], input[type=email], input[type=password], input[type=tel], input[type=url], input[type=search], input:not([type]), textarea')
            ->each(function (Crawler $field) use ($form, $value, &$filled): void {
                $name = $field->attr('name');
                if ($name === null || str_contains($name, '_token') || !$form->has($name) || $field->attr('disabled') !== null) {
                    return;
                }
                $form[$name]->setValue($value);
                $filled++;
            });

        return $filled;
    }

    public function testNoFormProducesAServerError(): void
    {
        $disposableId = $this->createDisposableAdministrator();
        $router = static::getContainer()->get('router');

        $submitted = 0;
        $failures = [];
        foreach ($this->formRoutes() as [$name, $variables]) {
            // enviar datos basura al formulario del propio usuario (por ejemplo, cambiarle la contraseña)
            // puede cerrar su sesión: se vuelve a entrar antes de cada ruta
            $this->login($this->em->find(Person::class, $disposableId));
            $combinations = $this->urlParameters($variables, DemoData::ORG_A_CODE, self::MAX_ID);
            if ($combinations === [] || count($combinations) > self::MAX_COMBINATIONS) {
                continue;
            }
            foreach ($combinations as $parameters) {
                $url = $router->generate($name, $parameters);
                $crawler = $this->client->request('GET', $url);
                if ($this->client->getResponse()->getStatusCode() >= 500) {
                    $failures[] = sprintf(
                        '%s %s [carga de la página] → HTTP %d %s',
                        $name,
                        json_encode($parameters),
                        $this->client->getResponse()->getStatusCode(),
                        self::exceptionMessage($this->client->getResponse()->headers->get('X-Debug-Exception'))
                    );
                }
                if ($this->client->getResponse()->getStatusCode() !== 200) {
                    continue;
                }
                $formCount = iterator_count($this->formsOf($crawler));
                for ($index = 0; $index < $formCount; $index++) {
                    foreach (self::VARIANTS as $label => $value) {
                        // el formulario se vuelve a leer para que cada variante parta del estado original
                        $crawler = $this->client->request('GET', $url);
                        $forms = iterator_to_array($this->formsOf($crawler), false);
                        if (!isset($forms[$index])) {
                            continue;
                        }
                        $form = $forms[$index];
                        if ($this->fillTextFields($form, $value ?? str_repeat('A', 300)) === 0) {
                            continue 2;
                        }
                        $this->client->submit($form);
                        $this->em->clear();
                        $submitted++;
                        if ($this->client->getResponse()->getStatusCode() >= 500) {
                            $failures[] = sprintf(
                                '%s %s [%s] → HTTP %d %s',
                                $name,
                                json_encode($parameters),
                                $label,
                                $this->client->getResponse()->getStatusCode(),
                                self::exceptionMessage($this->client->getResponse()->headers->get('X-Debug-Exception'))
                            );
                        }
                    }
                }
            }
        }

        self::assertGreaterThan(40, $submitted, 'Se han enviado muy pocos formularios: la prueba no es concluyente');
        self::assertSame([], $failures, "Formularios que producen un error del servidor:\n" . implode("\n", $failures));
    }

    private static function exceptionMessage(?string $header): string
    {
        return $header === null ? '' : mb_substr(rawurldecode($header), 0, 160);
    }
}
