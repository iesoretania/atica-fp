<?php

namespace App\Tests\Functional;

use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\Subject;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Resultados de aprendizaje de una materia: listado, alta, edición, borrado, importación y exportación en texto.
 * Cada materia de las fixtures tiene un resultado "RA1".
 */
class LearningOutcomeTest extends AppWebTestCase
{
    private const BASE = '/centro/ensenanza/materia';

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function subjectId(string $code = DemoData::ORG_A_CODE, int $index = 0): int
    {
        return $this->em()->getRepository(Subject::class)->createQueryBuilder('s')
            ->join('s.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')
            ->where('ay.organization = :organization')->setParameter('organization', $this->getOrganization($code))
            ->orderBy('s.id')->getQuery()->getResult()[$index]->getId();
    }

    /**
     * @return array<string, string> descripción de cada resultado por código
     */
    private function outcomes(int $subjectId): array
    {
        $result = [];
        foreach ($this->em()->getRepository(LearningOutcome::class)->findBy(['subject' => $subjectId], ['code' => 'ASC']) as $outcome) {
            $result[$outcome->getCode()] = $outcome->getDescription();
        }

        return $result;
    }

    private function outcomeId(int $subjectId, string $code): int
    {
        return $this->em()->getRepository(LearningOutcome::class)->findOneBy(['subject' => $subjectId, 'code' => $code])->getId();
    }

    /**
     * @return list<string>
     */
    private function readPaths(int $subject, int $outcome): array
    {
        return ["/$subject/resultado/1/", "/$subject/resultado/nuevo", "/resultado/$outcome", "/resultado/exportar/$subject"];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'coordinador de FP dual' => ['alfa.coordinador'];
        yield 'jefe de departamento' => ['alfa.jefe'];
    }

    /**
     * @dataProvider managerProvider
     */
    public function testManagersOpenTheScreensAndExport(string $username): void
    {
        $subject = $this->subjectId();
        $outcome = $this->outcomeId($subject, 'RA1');
        $this->login($this->getUser($username));

        foreach ($this->readPaths($subject, $outcome) as $path) {
            $this->client->request('GET', self::BASE . $path);
            self::assertResponseIsSuccessful($path);
        }
        self::assertStringStartsWith('RA1: ', (string) $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['tutor', 'profesor', 'tutorlaboral', 'alumno1', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotSeeOrChangeOutcomes(string $username): void
    {
        $subject = $this->subjectId();
        $outcome = $this->outcomeId($subject, 'RA1');
        $before = $this->outcomes($subject);
        $this->login($this->getUser($username));

        foreach ($this->readPaths($subject, $outcome) as $path) {
            $this->client->request('GET', self::BASE . $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', self::BASE . "/resultado/eliminar/$subject", ['items' => [$outcome], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', self::BASE . "/resultado/importar/$subject", ['data' => "RA9: Resultado ajeno\n"]);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->outcomes($subject));
    }

    public function testAdministratorOfAnotherOrganizationCannotUseThem(): void
    {
        $subject = $this->subjectId();
        $outcome = $this->outcomeId($subject, 'RA1');
        $before = $this->outcomes($subject);
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ($this->readPaths($subject, $outcome) as $path) {
            $this->client->request('GET', self::BASE . $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', self::BASE . "/resultado/eliminar/$subject", ['items' => [$outcome], 'confirm' => 'ok']);
        $this->client->request('POST', self::BASE . "/resultado/importar/$subject", ['data' => "RA9: Resultado ajeno\n"]);

        self::assertSame($before, $this->outcomes($subject));
    }

    public function testManagerCreatesAndEditsAnOutcome(): void
    {
        $subject = $this->subjectId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', self::BASE . "/$subject/resultado/nuevo");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'learning_outcome[code]' => 'RA2', 'learning_outcome[description]' => 'Segundo resultado',
        ]));
        self::assertResponseRedirects();
        self::assertArrayHasKey('RA2', $this->outcomes($subject));

        $id = $this->outcomeId($subject, 'RA2');
        $crawler = $this->client->request('GET', self::BASE . "/resultado/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['learning_outcome[description]' => 'Resultado corregido']));
        self::assertResponseRedirects();
        self::assertSame('Resultado corregido', $this->outcomes($subject)['RA2']);
    }

    public function testOutcomeWithoutCodeOrDescriptionIsRejected(): void
    {
        $subject = $this->subjectId();
        $before = $this->outcomes($subject);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', self::BASE . "/$subject/resultado/nuevo");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'learning_outcome[code]' => '', 'learning_outcome[description]' => '',
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->outcomes($subject));
    }

    public function testDeletionRequiresConfirmation(): void
    {
        $subject = $this->subjectId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('POST', self::BASE . "/resultado/importar/$subject", ['data' => "RA2: Para borrar\n"]);
        $id = $this->outcomeId($subject, 'RA2');

        $this->client->request('POST', self::BASE . "/resultado/eliminar/$subject", ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('RA2', $this->outcomes($subject));

        $this->client->request('POST', self::BASE . "/resultado/eliminar/$subject", ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertArrayNotHasKey('RA2', $this->outcomes($subject));
    }

    public function testOutcomesOfAnotherSubjectCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->subjectId(DemoData::ORG_A_CODE, 0);
        $other = $this->subjectId(DemoData::ORG_A_CODE, 1);
        $otherOutcome = $this->outcomeId($other, 'RA1');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/resultado/eliminar/$own", ['items' => [$otherOutcome], 'confirm' => 'ok']);

        self::assertArrayHasKey('RA1', $this->outcomes($other));
    }

    public function testImportCreatesMissingOutcomesAndKeepsExistingOnes(): void
    {
        $subject = $this->subjectId();
        $original = $this->outcomes($subject)['RA1'];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/resultado/importar/$subject", [
            'data' => "RA1: Descripción distinta\r\nRA2: Segundo resultado\r\nlínea sin formato\r\n\r\nRA3: Tercero\r\n",
        ]);

        self::assertResponseRedirects();
        $outcomes = $this->outcomes($subject);
        self::assertSame($original, $outcomes['RA1'], 'Un resultado que ya existe no se modifica');
        // con saltos de línea de Windows no se guarda el \r final
        self::assertSame('Segundo resultado', $outcomes['RA2']);
        self::assertSame('Tercero', $outcomes['RA3']);

        // importar de nuevo no duplica nada
        $this->client->request('POST', self::BASE . "/resultado/importar/$subject", ['data' => "RA2: Segundo resultado\n"]);
        self::assertCount(3, $this->outcomes($subject));
    }

    public function testEmptyAndHostileImportsDoNotFail(): void
    {
        $subject = $this->subjectId();
        $before = $this->outcomes($subject);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/resultado/importar/$subject", ['data' => "  \n"]);
        self::assertSame($before, $this->outcomes($subject));

        foreach ([str_repeat('x', 70000), '<script>alert(1)</script>', "ñandú\0nulo"] as $description) {
            $this->client->request('POST', self::BASE . "/resultado/importar/$subject", ['data' => "RA9: $description\n"]);
            self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        }
    }

    public function testExportRoundTripsWithImport(): void
    {
        $subject = $this->subjectId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('POST', self::BASE . "/resultado/importar/$subject", ['data' => "RA2: Segundo\nRA3: Tercero\n"]);

        $this->client->request('GET', self::BASE . "/resultado/exportar/$subject");

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString("RA2: Segundo\n", $content);
        self::assertStringContainsString("RA3: Tercero\n", $content);
    }
}
