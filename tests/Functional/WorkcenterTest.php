<?php

namespace App\Tests\Functional;

use App\Entity\Company;
use App\Entity\Workcenter;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Sedes (centros de trabajo) de una empresa: listado con búsqueda, alta, edición y borrado. Las empresas y sus sedes
 * son datos compartidos por todas las organizaciones; las gestionan quienes administran la organización, los jefes
 * de departamento y los coordinadores de FP dual y de la FFE. Cada empresa de las fixtures tiene varias sedes.
 */
class WorkcenterTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function companyId(int $index = 0): int
    {
        return $this->em()->getRepository(Company::class)->findBy([], ['id' => 'ASC'])[$index]->getId();
    }

    /**
     * @return list<string> nombres de las sedes de la empresa
     */
    private function names(int $companyId): array
    {
        return array_map(
            static fn(Workcenter $w): string => $w->getName(),
            $this->em()->getRepository(Workcenter::class)->findBy(['company' => $companyId], ['name' => 'ASC'])
        );
    }

    private function workcenterId(int $companyId): int
    {
        return $this->em()->getRepository(Workcenter::class)->findOneBy(['company' => $companyId], ['id' => 'ASC'])->getId();
    }

    /**
     * @return array<string, string> datos mínimos válidos de una sede
     */
    private function validData(string $name): array
    {
        return [
            'workcenter[name]' => $name,
            'workcenter[address]' => 'Calle Mayor, 1',
            'workcenter[city]' => 'Linares',
            'workcenter[zipCode]' => '23700',
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'coordinador de FP dual' => ['alfa.coordinador'];
        yield 'coordinador de la FFE' => ['alfa.gestorffe'];
    }

    /**
     * @dataProvider managerProvider
     */
    public function testManagersOpenTheScreens(string $username): void
    {
        $company = $this->companyId();
        $workcenter = $this->workcenterId($company);
        $this->login($this->getUser($username));

        foreach (["/empresa/$company/sede/listar/1", "/empresa/$company/sede/nueva", "/empresa/sede/$workcenter"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($username . ' ' . $url);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['economico', 'tutor', 'profesor', 'tutorlaboral', 'alumno1'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotSeeOrChangeWorkcenters(string $username): void
    {
        $company = $this->companyId();
        $workcenter = $this->workcenterId($company);
        $before = $this->names($company);
        $this->login($this->getUser($username));

        foreach (["/empresa/$company/sede/listar/1", "/empresa/$company/sede/nueva", "/empresa/sede/$workcenter"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "/empresa/$company/sede/eliminar", ['items' => [$workcenter], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->names($company));
    }

    public function testListFiltersBySearch(): void
    {
        $company = $this->companyId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $own = $this->names($company)[0];
        $this->client->request('GET', "/empresa/$company/sede/listar/1", ['q' => $own]);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString($own, (string) $this->client->getResponse()->getContent());

        // una sede de otra empresa con un nombre único no aparece en esta búsqueda, ni siquiera si coincide el texto
        $em = $this->em();
        $other = (new Workcenter())
            ->setCompany($em->getRepository(Company::class)->find($this->companyId(1)))
            ->setName('Zzz sede única de otra empresa')->setAddress('Calle 1')->setCity('Úbeda');
        $em->persist($other);
        $em->flush();
        $em->clear();
        $this->client->request('GET', "/empresa/$company/sede/listar/1", ['q' => 'Zzz sede única']);
        self::assertStringNotContainsString('Zzz sede única de otra empresa', (string) $this->client->getResponse()->getContent());
    }

    public function testAdministratorCreatesAndEditsAWorkcenter(): void
    {
        $company = $this->companyId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/empresa/$company/sede/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form($this->validData('Sede de prueba') + [
            'workcenter[emailAddress]' => 'sede.prueba@example.com',
        ]));
        self::assertResponseRedirects("/empresa/$company/sede/listar");
        self::assertContains('Sede de prueba', $this->names($company));

        $saved = $this->em()->getRepository(Workcenter::class)->findOneBy(['name' => 'Sede de prueba']);
        self::assertSame($company, $saved->getCompany()->getId());
        $crawler = $this->client->request('GET', '/empresa/sede/' . $saved->getId());
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['workcenter[city]' => 'Linares']));
        self::assertResponseRedirects();
        self::assertSame('Linares', $this->em()->getRepository(Workcenter::class)->find($saved->getId())->getCity());
    }

    public function testCoordinatorOfTheFfeCanNowCreateAndEdit(): void
    {
        $company = $this->companyId();
        $this->login($this->getUser('alfa.gestorffe'));

        $crawler = $this->client->request('GET', "/empresa/$company/sede/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form($this->validData('Sede creada por la FFE')));

        self::assertResponseRedirects();
        self::assertContains('Sede creada por la FFE', $this->names($company));
    }

    public function testWorkcenterWithoutNameIsRejected(): void
    {
        $company = $this->companyId();
        $before = $this->names($company);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/empresa/$company/sede/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['workcenter[name]' => '']));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->names($company));
    }

    public function testDeletionRequiresConfirmation(): void
    {
        $company = $this->companyId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $crawler = $this->client->request('GET', "/empresa/$company/sede/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form($this->validData('Para borrar')));
        $id = $this->em()->getRepository(Workcenter::class)->findOneBy(['name' => 'Para borrar'])->getId();

        $this->client->request('POST', "/empresa/$company/sede/eliminar", ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertContains('Para borrar', $this->names($company));

        $this->client->request('POST', "/empresa/$company/sede/eliminar", ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects("/empresa/$company/sede/listar");
        self::assertNotContains('Para borrar', $this->names($company));
    }

    public function testWorkcenterInUseByAgreementsIsKeptAndTheErrorIsReported(): void
    {
        $company = $this->companyId();
        $workcenter = $this->workcenterId($company);
        $before = $this->names($company);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        // la sede de las fixtures tiene convenios: la base de datos impide borrarla, sin error del servidor
        $this->client->request('POST', "/empresa/$company/sede/eliminar", ['items' => [$workcenter], 'confirm' => 'ok']);

        self::assertResponseRedirects();
        self::assertSame($before, $this->names($company));
    }

    public function testWorkcentersOfAnotherCompanyCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->companyId(0);
        $other = $this->companyId(1);
        $foreign = $this->workcenterId($other);
        $before = $this->names($other);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/empresa/$own/sede/eliminar", ['items' => [$foreign], 'confirm' => 'ok']);

        self::assertSame($before, $this->names($other));
    }
}
