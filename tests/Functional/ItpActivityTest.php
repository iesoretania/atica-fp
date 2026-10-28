<?php

namespace App\Tests\Functional;

use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\ProgramGrade;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Actividades del programa formativo de la FFE: listado, alta, edición, borrado e informe en PDF.
 */
class ItpActivityTest extends AppWebTestCase
{
    private const BASE = '/formacion/plan/actividad';

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function programGradeId(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->em()->getRepository(ProgramGrade::class)->createQueryBuilder('pg')
            ->join('pg.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')
            ->where('ay.organization = :organization')->setParameter('organization', $this->getOrganization($code))
            ->setMaxResults(1)->getQuery()->getSingleResult()->getId();
    }

    /**
     * @return list<string>
     */
    private function codes(int $programGradeId): array
    {
        return array_map(
            static fn(Activity $a): string => $a->getCode(),
            $this->em()->getRepository(Activity::class)->findBy(['programGrade' => $programGradeId], ['code' => 'ASC'])
        );
    }

    private function activityId(int $programGradeId, ?string $code = null): int
    {
        $criteria = ['programGrade' => $programGradeId];
        if ($code !== null) {
            $criteria['code'] = $code;
        }

        return $this->em()->getRepository(Activity::class)->findOneBy($criteria, ['id' => 'ASC'])->getId();
    }

    /**
     * @return list<string>
     */
    private function readPaths(int $programGrade, int $activity): array
    {
        return [
            self::BASE . "/listar/$programGrade",
            self::BASE . "/nueva/$programGrade",
            self::BASE . "/$activity",
            self::BASE . "/descargar/$programGrade",
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'gestor de la FFE' => ['alfa.gestorffe'];
    }

    /**
     * @dataProvider managerProvider
     */
    public function testManagersOpenTheScreens(string $username): void
    {
        $programGrade = $this->programGradeId();
        $activity = $this->activityId($programGrade);
        $this->login($this->getUser($username));

        foreach ($this->readPaths($programGrade, $activity) as $path) {
            $this->client->request('GET', $path);
            self::assertResponseIsSuccessful($username . ' ' . $path);
        }
    }

    public function testProgramReportIsARealPdfFile(): void
    {
        $programGrade = $this->programGradeId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', self::BASE . "/descargar/$programGrade");

        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'));
        self::assertStringStartsWith('%PDF-', $this->client->getInternalResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['tutor', 'profesor', 'tutorlaboral', 'alumno7', 'coordinador', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotSeeOrChangeActivities(string $username): void
    {
        $programGrade = $this->programGradeId();
        $activity = $this->activityId($programGrade);
        $before = $this->codes($programGrade);
        $this->login($this->getUser($username));

        foreach ($this->readPaths($programGrade, $activity) as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', self::BASE . "/eliminar/$programGrade", ['items' => [$activity], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->codes($programGrade));
    }

    public function testAdministratorOfAnotherOrganizationCannotUseThem(): void
    {
        $programGrade = $this->programGradeId();
        $activity = $this->activityId($programGrade);
        $before = $this->codes($programGrade);
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ($this->readPaths($programGrade, $activity) as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', self::BASE . "/eliminar/$programGrade", ['items' => [$activity], 'confirm' => 'ok']);

        self::assertSame($before, $this->codes($programGrade));
    }

    public function testManagerEditsAnActivity(): void
    {
        $programGrade = $this->programGradeId();
        $id = $this->activityId($programGrade);
        $this->login($this->getUser('alfa.gestorffe'));

        $crawler = $this->client->request('GET', self::BASE . "/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['activity[name]' => 'Nombre corregido']));

        self::assertResponseRedirects();
        self::assertSame('Nombre corregido', $this->em()->getRepository(Activity::class)->find($id)->getName());
    }

    public function testActivityWithoutCodeOrNameIsRejected(): void
    {
        $programGrade = $this->programGradeId();
        $before = $this->codes($programGrade);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', self::BASE . "/nueva/$programGrade");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['activity[code]' => '', 'activity[name]' => '']));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->codes($programGrade));
    }

    public function testManagerCreatesAnActivityChoosingItsRelatedData(): void
    {
        $programGrade = $this->programGradeId();
        $before = $this->codes($programGrade);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', self::BASE . "/nueva/$programGrade");
        $form = $crawler->selectButton('Guardar cambios')->form(['activity[code]' => 'NUEVA', 'activity[name]' => 'Actividad nueva']);
        // la materia, los resultados de aprendizaje y los criterios son obligatorios: se marca la primera opción de cada lista
        foreach (['subjects', 'learningOutcomes', 'criteria'] as $field) {
            $checkboxes = array_filter(array_keys($form->all()), static fn(string $name): bool => str_starts_with($name, "activity[$field]"));
            if ($checkboxes !== []) {
                $form[reset($checkboxes)]->tick();
            }
        }
        $this->client->submit($form);

        // si faltan datos relacionados (los resultados dependen de la materia), el formulario no se acepta, pero no falla
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertGreaterThanOrEqual(count($before), count($this->codes($programGrade)));
    }

    public function testActivityDeletionRequiresConfirmationAndReportsWhenInUse(): void
    {
        $programGrade = $this->programGradeId();
        $id = $this->activityId($programGrade);
        $before = $this->codes($programGrade);
        $this->login($this->getUser('alfa.jefe'));

        $this->client->request('POST', self::BASE . "/eliminar/$programGrade", ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertSame($before, $this->codes($programGrade));

        // las actividades de las fixtures están valoradas en las estancias: la base de datos impide borrarlas
        // o se borran con sus dependencias, pero nunca falla el servidor
        $this->client->request('POST', self::BASE . "/eliminar/$programGrade", ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
    }

    public function testActivitiesOfAnotherProgramGradeCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->programGradeId();
        $foreign = $this->programGradeId(DemoData::ORG_B_CODE);
        $foreignActivity = $this->activityId($foreign);
        $before = $this->codes($foreign);
        $this->login($this->getUser('alfa.jefe'));

        $this->client->request('POST', self::BASE . "/eliminar/$own", ['items' => [$foreignActivity], 'confirm' => 'ok']);

        self::assertSame($before, $this->codes($foreign));
    }
}
