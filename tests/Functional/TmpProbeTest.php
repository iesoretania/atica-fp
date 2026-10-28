<?php
namespace App\Tests\Functional;
class TmpProbeTest extends AppWebTestCase {
    use ResolvesUrlParametersTrait;
    private $em;
    public function testProbe(): void {
        $this->em = static::getContainer()->get('doctrine')->getManager();
        $this->login($this->getUser('alfa.admin'));
        $router = static::getContainer()->get('router');
        foreach ($router->getRouteCollection() as $name => $route) {
            if (!preg_match('#^/(fct|dual|formacion)/informe/#', $route->getPath()) || str_contains($route->getPath(), 'listar')) continue;
            $vars = array_values(array_diff($route->compile()->getVariables(), ['page']));
            foreach (array_slice($this->urlParameters($vars, '23000001', 3), 0, 1) as $p) {
                $this->client->request('GET', $router->generate($name, $p));
                $r = $this->client->getResponse();
                file_put_contents(getenv('PROBE_OUT'), sprintf("%s %s %d %s\n", $name, json_encode($p), $r->getStatusCode(), $r->headers->get('Content-Type')), FILE_APPEND);
                $this->em->clear();
            }
        }
        self::assertTrue(true);
    }
}
