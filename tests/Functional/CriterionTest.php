<?php

namespace App\Tests\Functional;

use App\Entity\Edu\Criterion;
use App\Entity\Edu\LearningOutcome;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Criterios de evaluación de un resultado de aprendizaje: listado, alta, edición, borrado, importación y
 * exportación en texto. Cada resultado de las fixtures tiene un criterio "a".
 */
class CriterionTest extends AppWebTestCase
{
    private const BASE = '/centro/ensenanza/materia/resultado';

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function outcomeId(string $code = DemoData::ORG_A_CODE, int $index = 0): int
    {
        return $this->em()->getRepository(LearningOutcome::class)->createQueryBuilder('lo')
            ->join('lo.subject', 's')->join('s.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')
            ->where('ay.organization = :organization')->setParameter('organization', $this->getOrganization($code))
            ->orderBy('lo.id')->getQuery()->getResult()[$index]->getId();
    }

    /**
     * @return array<string, string> nombre de cada criterio por código
     */
    private function criteria(int $outcomeId): array
    {
        $result = [];
        foreach ($this->em()->getRepository(Criterion::class)->findBy(['learningOutcome' => $outcomeId], ['code' => 'ASC']) as $criterion) {
            $result[$criterion->getCode()] = $criterion->getName();
        }

        return $result;
    }

    private function criterionId(int $outcomeId, string $code): int
    {
        return $this->em()->getRepository(Criterion::class)->findOneBy(['learningOutcome' => $outcomeId, 'code' => $code])->getId();
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
        $outcome = $this->outcomeId();
        $criterion = $this->criterionId($outcome, 'a');
        $this->login($this->getUser($username));

        foreach (["/$outcome/criterio/1/", "/$outcome/criterio/nuevo", "/criterio/$criterion", "/criterio/exportar/$outcome"] as $path) {
            $this->client->request('GET', self::BASE . $path);
            self::assertResponseIsSuccessful($path);
        }
        self::assertSame("a) Criterio a\n", $this->client->getResponse()->getContent());
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
    public function testOthersCannotSeeOrChangeCriteria(string $username): void
    {
        $outcome = $this->outcomeId();
        $criterion = $this->criterionId($outcome, 'a');
        $before = $this->criteria($outcome);
        $this->login($this->getUser($username));

        foreach (["/$outcome/criterio/1/", "/$outcome/criterio/nuevo", "/criterio/$criterion", "/criterio/exportar/$outcome"] as $path) {
            $this->client->request('GET', self::BASE . $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', self::BASE . "/criterio/eliminar/$outcome", ['items' => [$criterion], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', self::BASE . "/criterio/importar/$outcome", ['data' => "z) Criterio ajeno\n"]);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->criteria($outcome));
    }

    public function testAdministratorOfAnotherOrganizationCannotUseThem(): void
    {
        $outcome = $this->outcomeId();
        $criterion = $this->criterionId($outcome, 'a');
        $before = $this->criteria($outcome);
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach (["/$outcome/criterio/1/", "/$outcome/criterio/nuevo", "/criterio/$criterion", "/criterio/exportar/$outcome"] as $path) {
            $this->client->request('GET', self::BASE . $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', self::BASE . "/criterio/eliminar/$outcome", ['items' => [$criterion], 'confirm' => 'ok']);
        $this->client->request('POST', self::BASE . "/criterio/importar/$outcome", ['data' => "z) Criterio ajeno\n"]);

        self::assertSame($before, $this->criteria($outcome));
    }

    public function testManagerCreatesAndEditsACriterion(): void
    {
        $outcome = $this->outcomeId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', self::BASE . "/$outcome/criterio/nuevo");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'criterion[code]' => 'b', 'criterion[name]' => 'Segundo criterio',
        ]));
        self::assertResponseRedirects();
        self::assertSame(['a' => 'Criterio a', 'b' => 'Segundo criterio'], $this->criteria($outcome));

        $id = $this->criterionId($outcome, 'b');
        $crawler = $this->client->request('GET', self::BASE . "/criterio/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['criterion[name]' => 'Criterio corregido']));
        self::assertResponseRedirects();
        self::assertSame('Criterio corregido', $this->criteria($outcome)['b']);
    }

    public function testCriterionWithoutCodeOrNameIsRejected(): void
    {
        $outcome = $this->outcomeId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', self::BASE . "/$outcome/criterio/nuevo");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['criterion[code]' => '', 'criterion[name]' => '']));

        self::assertResponseStatusCodeSame(200);
        self::assertSame(['a' => 'Criterio a'], $this->criteria($outcome));
    }

    public function testDeletionRequiresConfirmation(): void
    {
        $outcome = $this->outcomeId();
        $criterion = $this->criterionId($outcome, 'a');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/criterio/eliminar/$outcome", ['items' => [$criterion]]);
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->criteria($outcome));

        $this->client->request('POST', self::BASE . "/criterio/eliminar/$outcome", ['items' => [$criterion], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        // el criterio puede estar en uso por actividades: o se borra, o se informa sin fallar
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    public function testCriteriaOfAnotherOutcomeCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->outcomeId(DemoData::ORG_A_CODE, 0);
        $other = $this->outcomeId(DemoData::ORG_A_CODE, 1);
        $otherCriterion = $this->criterionId($other, 'a');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/criterio/eliminar/$own", ['items' => [$otherCriterion], 'confirm' => 'ok']);

        self::assertSame(['a' => 'Criterio a'], $this->criteria($other));
    }

    // --- Importación ---------------------------------------------------------------------------------

    public function testImportCreatesAndUpdatesCriteriaWithoutDuplicates(): void
    {
        $outcome = $this->outcomeId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/criterio/importar/$outcome", [
            'data' => "a) Criterio a reescrito\nb) Criterio nuevo\nlínea sin formato\n\nc) Otro criterio\n",
        ]);

        self::assertResponseRedirects();
        self::assertSame(
            ['a' => 'Criterio a reescrito', 'b' => 'Criterio nuevo', 'c' => 'Otro criterio'],
            $this->criteria($outcome)
        );

        // importar de nuevo lo mismo no duplica nada
        $this->client->request('POST', self::BASE . "/criterio/importar/$outcome", ['data' => "b) Criterio nuevo\n"]);
        self::assertCount(3, $this->criteria($outcome));
    }

    public function testImportWithWindowsLineEndingsDoesNotKeepTheCarriageReturn(): void
    {
        $outcome = $this->outcomeId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/criterio/importar/$outcome", ['data' => "b) Criterio con salto\r\nc) Otro\r\n"]);

        $criteria = $this->criteria($outcome);
        self::assertSame('Criterio con salto', $criteria['b']);
        self::assertSame('Otro', $criteria['c']);
    }

    public function testEmptyImportChangesNothingAndHostileImportsDoNotFail(): void
    {
        $outcome = $this->outcomeId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/criterio/importar/$outcome", ['data' => "  \n"]);
        self::assertResponseRedirects();
        self::assertSame(['a' => 'Criterio a'], $this->criteria($outcome));

        // nombre larguísimo, código con acentos y contenido con etiquetas: nunca un error del servidor
        foreach ([str_repeat('x', 70000), '<script>alert(1)</script>', "ñandú\0nulo"] as $name) {
            $this->client->request('POST', self::BASE . "/criterio/importar/$outcome", ['data' => "z) $name\n"]);
            self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        }
    }

    public function testExportRoundTripsWithImport(): void
    {
        $outcome = $this->outcomeId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('POST', self::BASE . "/criterio/importar/$outcome", ['data' => "b) Segundo\nc) Tercero\n"]);

        $this->client->request('GET', self::BASE . "/criterio/exportar/$outcome");

        self::assertSame("a) Criterio a\nb) Segundo\nc) Tercero\n", $this->client->getResponse()->getContent());
    }
}
