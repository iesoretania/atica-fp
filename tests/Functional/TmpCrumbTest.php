<?php
namespace App\Tests\Functional;
class TmpCrumbTest extends AppWebTestCase
{
    public function testDump(): void
    {
        $this->login($this->getUser('alfa.admin'));
        $seen = []; $queue = ['/'];
        $out = [];
        while ($queue && count($seen) < 160) {
            $p = array_shift($queue);
            if (isset($seen[$p])) continue;
            $seen[$p] = 1;
            $c = $this->client->request('GET', $p);
            if ($this->client->getResponse()->getStatusCode() >= 400) continue;
            $crumbs = $c->filter('nav[aria-label="Breadcrumb"] li, ol.breadcrumb li, main ol li')->each(fn($n) => trim(preg_replace('/\s+/', ' ', $n->text())));
            $crumbs = array_values(array_filter($crumbs, fn($t) => $t !== '/' && $t !== ''));
            $h1 = $c->filter('main h1')->count() ? trim($c->filter('main h1')->first()->text()) : '-';
            $out[] = count($crumbs) . ' | ' . implode(' > ', $crumbs) . ' || h1: ' . $h1 . '   [' . $p . ']';
            foreach ($c->filter('main a[href], nav a[href]') as $a) {
                $h = $a->getAttribute('href');
                if (str_starts_with($h, '/') && !str_contains($h, 'salir') && !str_contains($h, 'switch') && !str_contains($h, 'borrar') && !str_contains($h, 'exportar') && !str_contains($h, 'descargar') && !str_contains($h, 'pdf') && !str_contains($h, '/api/') && !str_contains($h, 'bloquear') && !str_contains($h, 'importar')) $queue[] = strtok($h, '?#');
            }
        }
        file_put_contents(getenv('TMPDIR') . '/crumbs.txt', implode("\n", $out));
        self::assertTrue(true);
    }
}
