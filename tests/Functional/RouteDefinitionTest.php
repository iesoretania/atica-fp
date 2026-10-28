<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Comprobaciones sobre la definición de las rutas: un requisito que no corresponde a ninguna variable de la ruta
 * (por ejemplo por un nombre mal escrito) no valida nada, y un requisito de un solo dígito deja sin ruta a los
 * identificadores a partir de 10.
 */
class RouteDefinitionTest extends KernelTestCase
{
    public function testEveryRequirementAppliesToAVariableOfTheRoute(): void
    {
        self::bootKernel();
        $wrong = [];
        foreach (static::getContainer()->get('router')->getRouteCollection() as $name => $route) {
            $variables = $route->compile()->getVariables();
            foreach (array_keys($route->getRequirements()) as $requirement) {
                if (!in_array($requirement, $variables, true) && !in_array($requirement, ['_method', '_scheme'], true)) {
                    $wrong[] = sprintf('%s (%s): "%s" no es una variable de la ruta', $name, $route->getPath(), $requirement);
                }
            }
        }

        self::assertSame([], $wrong, implode("\n", $wrong));
    }

    public function testNoRequirementLimitsAnIdentifierToASingleDigit(): void
    {
        self::bootKernel();
        $wrong = [];
        foreach (static::getContainer()->get('router')->getRouteCollection() as $name => $route) {
            foreach ($route->getRequirements() as $variable => $pattern) {
                if (in_array($pattern, ['\d', '[0-9]'], true)) {
                    $wrong[] = sprintf('%s (%s): "%s" => %s', $name, $route->getPath(), $variable, $pattern);
                }
            }
        }

        self::assertSame([], $wrong, implode("\n", $wrong));
    }

    public function testReportRoutesAcceptIdentifiersWithSeveralDigits(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        foreach ([
            '/formacion/informe/encuesta/estudiantes/12/34',
            '/formacion/informe/encuesta/empresas/12/34',
            '/formacion/informe/encuesta/centro/12/34',
            '/dual/informe/encuesta/estudiantes/12/34',
            '/dual/informe/encuesta/empresas/12/34',
            '/dual/informe/encuesta/centro/12/34',
        ] as $path) {
            $match = $router->match($path);
            self::assertArrayHasKey('_route', $match, $path);
        }
    }
}
