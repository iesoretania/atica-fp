<?php

namespace App\Tests\Functional;

use App\Entity\Edu\Competency;
use App\Entity\Edu\Training;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Competencias de una enseñanza: listado, alta, edición, borrado, importación y exportación en texto.
 * Solo quien administra la organización las gestiona. La enseñanza de las fixtures tiene la competencia "CG1".
 */
class CompetencyTest extends AppWebTestCase
{
    private const BASE = '/centro/ensenanza';

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function trainingId(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->em()->getRepository(Training::class)->createQueryBuilder('t')
            ->join('t.academicYear', 'ay')
            ->where('ay.organization = :organization')->setParameter('organization', $this->getOrganization($code))
            ->setMaxResults(1)->getQuery()->getSingleResult()->getId();
    }

    /**
     * @return array<string, string> descripción de cada competencia por código
     */
    private function competencies(int $trainingId): array
    {
        $result = [];
        foreach ($this->em()->getRepository(Competency::class)->findBy(['training' => $trainingId], ['code' => 'ASC']) as $competency) {
            $result[$competency->getCode()] = $competency->getDescription();
        }

        return $result;
    }

    private function competencyId(int $trainingId, string $code): int
    {
        return $this->em()->getRepository(Competency::class)->findOneBy(['training' => $trainingId, 'code' => $code])->getId();
    }

    /**
     * @return list<string>
     */
    private function readPaths(int $training, int $competency): array
    {
        return ["/$training/competencial/1/", "/$training/competencia/nueva", "/competencia/$competency", "/competencia/exportar/$training"];
    }

    public function testAdministratorOpensTheScreensAndExports(): void
    {
        $training = $this->trainingId();
        $competency = $this->competencyId($training, 'CG1');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach ($this->readPaths($training, $competency) as $path) {
            $this->client->request('GET', self::BASE . $path);
            self::assertResponseIsSuccessful($path);
        }
        self::assertStringStartsWith('CG1) ', (string) $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['jefe', 'coordinador', 'tutor', 'profesor', 'tutorlaboral', 'alumno1', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOnlyOrganizationAdministratorsManageCompetencies(string $username): void
    {
        $training = $this->trainingId();
        $competency = $this->competencyId($training, 'CG1');
        $before = $this->competencies($training);
        $this->login($this->getUser($username));

        foreach ($this->readPaths($training, $competency) as $path) {
            $this->client->request('GET', self::BASE . $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        // incluidos el borrado y la importación, que antes admitían al jefe de departamento y al coordinador
        $this->client->request('POST', self::BASE . "/competencia/eliminar/$training", ['items' => [$competency], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', self::BASE . "/competencia/importar/$training", ['data' => "CG9) Competencia ajena\n"]);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->competencies($training));
    }

    public function testAdministratorOfAnotherOrganizationCannotUseThem(): void
    {
        $training = $this->trainingId();
        $competency = $this->competencyId($training, 'CG1');
        $before = $this->competencies($training);
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ($this->readPaths($training, $competency) as $path) {
            $this->client->request('GET', self::BASE . $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', self::BASE . "/competencia/eliminar/$training", ['items' => [$competency], 'confirm' => 'ok']);
        $this->client->request('POST', self::BASE . "/competencia/importar/$training", ['data' => "CG9) Competencia ajena\n"]);

        self::assertSame($before, $this->competencies($training));
    }

    public function testAdministratorCreatesAndEditsACompetency(): void
    {
        $training = $this->trainingId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', self::BASE . "/$training/competencia/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'competency[code]' => 'CG2', 'competency[description]' => 'Segunda competencia',
        ]));
        self::assertResponseRedirects();
        self::assertArrayHasKey('CG2', $this->competencies($training));

        $id = $this->competencyId($training, 'CG2');
        $crawler = $this->client->request('GET', self::BASE . "/competencia/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['competency[description]' => 'Competencia corregida']));
        self::assertResponseRedirects();
        self::assertSame('Competencia corregida', $this->competencies($training)['CG2']);
    }

    public function testCompetencyWithoutCodeOrDescriptionIsRejected(): void
    {
        $training = $this->trainingId();
        $before = $this->competencies($training);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', self::BASE . "/$training/competencia/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'competency[code]' => '', 'competency[description]' => '',
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->competencies($training));
    }

    public function testDeletionRequiresConfirmation(): void
    {
        $training = $this->trainingId();
        $competency = $this->competencyId($training, 'CG1');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/competencia/eliminar/$training", ['items' => [$competency]]);
        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('CG1', $this->competencies($training));

        $this->client->request('POST', self::BASE . "/competencia/eliminar/$training", ['items' => [$competency], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertArrayNotHasKey('CG1', $this->competencies($training));
    }

    public function testCompetenciesOfAnotherTrainingCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->trainingId();
        $foreign = $this->trainingId(DemoData::ORG_B_CODE);
        $foreignCompetency = $this->competencyId($foreign, 'CG1');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/competencia/eliminar/$own", ['items' => [$foreignCompetency], 'confirm' => 'ok']);

        self::assertArrayHasKey('CG1', $this->competencies($foreign));
    }

    public function testImportCreatesMissingCompetenciesWithoutTheCarriageReturn(): void
    {
        $training = $this->trainingId();
        $original = $this->competencies($training)['CG1'];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/competencia/importar/$training", [
            'data' => "CG1) Descripción distinta\r\nCG2) Segunda\r\nlínea sin formato\r\n\r\nCG3) Tercera\r\n",
        ]);

        self::assertResponseRedirects();
        $competencies = $this->competencies($training);
        self::assertSame($original, $competencies['CG1'], 'Una competencia que ya existe no se modifica');
        self::assertSame('Segunda', $competencies['CG2']);
        self::assertSame('Tercera', $competencies['CG3']);

        $this->client->request('POST', self::BASE . "/competencia/importar/$training", ['data' => "CG2) Segunda\n"]);
        self::assertCount(3, $this->competencies($training));
    }

    public function testEmptyAndHostileImportsDoNotFail(): void
    {
        $training = $this->trainingId();
        $before = $this->competencies($training);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/competencia/importar/$training", ['data' => "  \n"]);
        self::assertSame($before, $this->competencies($training));

        foreach ([str_repeat('x', 70000), '<script>alert(1)</script>', "ñandú\0nulo"] as $description) {
            $this->client->request('POST', self::BASE . "/competencia/importar/$training", ['data' => "CG9) $description\n"]);
            self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        }
    }

    public function testExportRoundTripsWithImport(): void
    {
        $training = $this->trainingId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('POST', self::BASE . "/competencia/importar/$training", ['data' => "CG2) Segunda\nCG3) Tercera\n"]);

        $this->client->request('GET', self::BASE . "/competencia/exportar/$training");

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString("CG2) Segunda\n", $content);
        self::assertStringContainsString("CG3) Tercera\n", $content);
    }
}
