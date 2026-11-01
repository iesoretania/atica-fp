<?php

namespace App\Tests\Functional;

use App\Entity\WptModule\Shift;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

/**
 * Listado dinámico de acuerdos de FCT (Live Component Wpt:AgreementList): filtro, paginación y permisos en cada renderizado.
 *  - convenio 1 (Tecnologías del Sur): alumno1 y alumno2; convenio 2 (Consultoría del Norte): alumno3
 */
class WptAgreementListComponentTest extends AppWebTestCase
{
    use InteractsWithLiveComponents;

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function shift(string $code = DemoData::ORG_A_CODE): Shift
    {
        return $this->em()->getRepository(Shift::class)->createQueryBuilder('s')
            ->join('s.subject', 'su')->join('su.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')
            ->where('ay.organization = :organization')->setParameter('organization', $this->getOrganization($code))
            ->setMaxResults(1)->getQuery()->getSingleResult();
    }

    private function table(string $html): string
    {
        return (new Crawler($html))->filter('#list')->text();
    }

    public function testFilterAndClearQuery(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->createLiveComponent('Wpt:AgreementList', ['shift' => $this->shift()], $this->client);

        // se mira solo la tabla: el desplegable de filtro por centro de trabajo lista todos los centros
        $html = $this->table($component->render()->toString());
        self::assertStringContainsString('Tecnologías del Sur', $html);
        self::assertStringContainsString('Consultoría del Norte', $html);

        $html = $this->table($component->set('q', 'Consultoría')->render()->toString());
        self::assertStringContainsString('Consultoría del Norte', $html);
        self::assertStringNotContainsString('Tecnologías del Sur', $html);

        $html = $this->table($component->call('clearQuery')->render()->toString());
        self::assertStringContainsString('Tecnologías del Sur', $html);
        self::assertSame('', $component->component()->q);
    }

    public function testSyncFromUrlRestoresStateAndIgnoresOutOfRangePage(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->createLiveComponent('Wpt:AgreementList', ['shift' => $this->shift()], $this->client);

        $html = $this->table($component->call('syncFromUrl', ['q' => 'Consultoría', 'page' => 1])->render()->toString());
        self::assertStringContainsString('Consultoría del Norte', $html);
        self::assertStringNotContainsString('Tecnologías del Sur', $html);

        // una página inexistente (p. ej. un enlace guardado) no falla: vuelve a la primera
        $html = $this->table($component->call('syncFromUrl', ['q' => '', 'page' => 999])->render()->toString());
        self::assertStringContainsString('Tecnologías del Sur', $html);
    }

    public function testRenderIsDeniedForAnotherOrganizationsShift(): void
    {
        $shiftOfOrganizationA = $this->shift();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        try {
            $this->createLiveComponent('Wpt:AgreementList', ['shift' => $shiftOfOrganizationA], $this->client)->render();
            self::fail('Se esperaba que se denegase el acceso');
        } catch (\Throwable $e) {
            // Twig envuelve la excepción al renderizar; el kernel de Symfony busca el AccessDenied en la cadena y responde 403
            while ($e->getPrevious() !== null && !$e instanceof AccessDeniedException) {
                $e = $e->getPrevious();
            }
            self::assertInstanceOf(AccessDeniedException::class, $e);
        }
    }

    public function testPageShowsComponentWithStateFromTheUrl(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $crawler = $this->client->request('GET', '/fct/acuerdo/' . $this->shift()->getId() . '/listar?q=Consultor%C3%ADa');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Consultoría del Norte', $crawler->filter('#list')->text());
        self::assertStringNotContainsString('Tecnologías del Sur', $crawler->filter('#list')->text());
        self::assertSame('Consultoría', $crawler->filter('input#search')->attr('value'));
        self::assertCount(1, $crawler->filter('[data-live-history-base-url-value]'));
    }
}
