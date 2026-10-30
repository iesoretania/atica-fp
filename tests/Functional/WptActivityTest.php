<?php

namespace App\Tests\Functional;

use App\Entity\WptModule\Activity;
use App\Entity\WptModule\Shift;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Actividades de la fase de FCT: alta, edición, listado, búsqueda y borrado. Las fixtures tienen una fase por
 * organización con tres actividades (A1, A2 y A3).
 */
class WptActivityTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function shiftId(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->em()->getRepository(Shift::class)->createQueryBuilder('s')
            ->join('s.subject', 'su')->join('su.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')
            ->where('ay.organization = :organization')->setParameter('organization', $this->getOrganization($code))
            ->setMaxResults(1)->getQuery()->getSingleResult()->getId();
    }

    private function activityCodes(int $shiftId): array
    {
        $codes = array_map(
            static fn(Activity $a): string => $a->getCode(),
            $this->em()->getRepository(Activity::class)->findBy(['shift' => $shiftId], ['code' => 'ASC'])
        );

        return $codes;
    }

    private function activityId(int $shiftId, string $code): int
    {
        return $this->em()->getRepository(Activity::class)->findOneBy(['shift' => $shiftId, 'code' => $code])->getId();
    }

    public function testManagerListsAndSearchesActivities(): void
    {
        $shiftId = $this->shiftId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', "/fct/actividad/$shiftId/listar");
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        foreach (['A1', 'A2', 'A3'] as $code) {
            self::assertStringContainsString($code, $content);
        }

        $this->client->request('GET', "/fct/actividad/$shiftId/listar", ['q' => 'Desarrollo']);
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Desarrollo de aplicaciones', $content);
        self::assertStringNotContainsString('Documentación y pruebas', $content);
    }

    public function testManagerCreatesAndEditsAnActivity(): void
    {
        $shiftId = $this->shiftId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/fct/actividad/nueva/$shiftId");
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'activity[code]' => 'A4',
            'activity[description]' => 'Mantenimiento de sistemas',
        ]));
        self::assertResponseRedirects("/fct/actividad/$shiftId/listar");
        self::assertSame(['A1', 'A2', 'A3', 'A4'], $this->activityCodes($shiftId));

        $id = $this->activityId($shiftId, 'A4');
        $crawler = $this->client->request('GET', "/fct/actividad/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['activity[description]' => 'Mantenimiento corregido']));
        self::assertResponseRedirects();
        self::assertSame('Mantenimiento corregido', $this->em()->getRepository(Activity::class)->find($id)->getDescription());
    }

    public function testImportWithWindowsLineEndingsDoesNotKeepTheCarriageReturn(): void
    {
        $shiftId = $this->shiftId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/fct/actividad/importar/$shiftId", ['data' => "A4: Mantenimiento\r\nA5: Redes\r\n"]);

        self::assertSame(['A1', 'A2', 'A3', 'A4', 'A5'], $this->activityCodes($shiftId));
        $activity = $this->em()->getRepository(Activity::class)->find($this->activityId($shiftId, 'A4'));
        self::assertSame('Mantenimiento', $activity->getDescription());
    }

    public function testActivityWithoutCodeOrDescriptionIsRejected(): void
    {
        $shiftId = $this->shiftId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/fct/actividad/nueva/$shiftId");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['activity[code]' => '', 'activity[description]' => '']));

        self::assertResponseStatusCodeSame(200);
        self::assertSame(['A1', 'A2', 'A3'], $this->activityCodes($shiftId));
    }

    public function testDeletionRequiresConfirmation(): void
    {
        $shiftId = $this->shiftId();
        $id = $this->activityId($shiftId, 'A3');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/fct/actividad/operacion/$shiftId", ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertSame(['A1', 'A2', 'A3'], $this->activityCodes($shiftId));

        $this->client->request('POST', "/fct/actividad/operacion/$shiftId", ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects("/fct/actividad/$shiftId/listar");
        self::assertSame(['A1', 'A2'], $this->activityCodes($shiftId));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonManagerProvider(): iterable
    {
        foreach (['tutor', 'profesor', 'tutorlaboral', 'alumno1', 'coordinador', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider nonManagerProvider
     */
    public function testOnlyManagersCanChangeActivities(string $username): void
    {
        $shiftId = $this->shiftId();
        $id = $this->activityId($shiftId, 'A1');
        $this->login($this->getUser($username));

        foreach (["GET /fct/actividad/$shiftId/listar", "GET /fct/actividad/nueva/$shiftId", "GET /fct/actividad/$id"] as $request) {
            [$method, $path] = explode(' ', $request);
            $this->client->request($method, $path);
            self::assertResponseStatusCodeSame(403, $request);
        }
        $this->client->request('POST', "/fct/actividad/operacion/$shiftId", ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame(['A1', 'A2', 'A3'], $this->activityCodes($shiftId));
    }

    public function testDepartmentHeadManagesActivitiesOfTheirOwnDepartment(): void
    {
        $shiftId = $this->shiftId();
        $this->login($this->getUser('alfa.jefe'));

        $this->client->request('GET', "/fct/actividad/$shiftId/listar");

        // el jefe de departamento gestiona las actividades si la fase es de su familia profesional
        self::assertResponseIsSuccessful();
    }

    public function testAdministratorOfAnotherOrganizationCannotUseThem(): void
    {
        $shiftId = $this->shiftId();
        $id = $this->activityId($shiftId, 'A1');
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach (["GET /fct/actividad/$shiftId/listar", "GET /fct/actividad/nueva/$shiftId", "GET /fct/actividad/$id"] as $request) {
            [$method, $path] = explode(' ', $request);
            $this->client->request($method, $path);
            self::assertResponseStatusCodeSame(403, $request);
        }
        $this->client->request('POST', "/fct/actividad/operacion/$shiftId", ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame(['A1', 'A2', 'A3'], $this->activityCodes($shiftId));
    }

    public function testActivitiesOfAnotherShiftCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->shiftId();
        $foreign = $this->shiftId(DemoData::ORG_B_CODE);
        $foreignActivity = $this->activityId($foreign, 'A1');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/fct/actividad/operacion/$own", ['items' => [$foreignActivity], 'confirm' => 'ok']);

        self::assertSame(['A1', 'A2', 'A3'], $this->activityCodes($foreign));
    }

    /**
     * Segunda convocatoria de la misma materia (sin actividades), destino de las copias
     */
    private function createEmptyShift(): int
    {
        $sourceId = $this->shiftId();
        $em = $this->em();
        $shift = (new Shift())
            ->setName('Zz convocatoria vacía')
            ->setType('FCT')
            ->setHours(30000)
            ->setQuarter(Shift::QUARTER_FIRST)
            ->setLocked(false)
            ->setSubject($em->find(Shift::class, $sourceId)->getSubject());
        $em->persist($shift);
        $em->flush();

        return $shift->getId();
    }

    private function criteriaCount(int $shiftId): int
    {
        return (int) $this->em()->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM wpt_activity_criterion ac JOIN wpt_activity a ON a.id = ac.activity_id WHERE a.shift_id = ?',
            [$shiftId]
        );
    }

    public function testActivitiesAreCopiedFromAnotherShiftOfTheSameSubject(): void
    {
        $source = $this->shiftId();
        $target = $this->createEmptyShift();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/fct/actividad/copiar/$target");
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Copiar')->count() > 0 ? $crawler->selectButton('Copiar')->form() : $crawler->filter('form')->last()->form();
        $form['activity_copy[shift]']->select((string) $source);
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(['A1', 'A2', 'A3'], $this->activityCodes($target));
        self::assertSame($this->criteriaCount($source), $this->criteriaCount($target), 'Se copian también los criterios');
    }

    public function testCopyingTwiceDoesNotDuplicateOrFail(): void
    {
        $source = $this->shiftId();
        $target = $this->createEmptyShift();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        for ($i = 0; $i < 2; $i++) {
            $crawler = $this->client->request('GET', "/fct/actividad/copiar/$target");
            $form = $crawler->selectButton('Copiar')->count() > 0 ? $crawler->selectButton('Copiar')->form() : $crawler->filter('form')->last()->form();
            $form['activity_copy[shift]']->select((string) $source);
            $this->client->submit($form);
            self::assertResponseRedirects();
            $crawler = $this->client->followRedirect();
            self::assertSame(0, $crawler->filter('.alert-danger')->count(), "Copia número " . ($i + 1) . ' fallida');
        }

        self::assertSame(['A1', 'A2', 'A3'], $this->activityCodes($target));
        self::assertSame($this->criteriaCount($source), $this->criteriaCount($target));
    }

    public function testActivitiesAreCopiedFromADualProject(): void
    {
        $target = $this->createEmptyShift();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/fct/actividad/copiar/dual/$target");
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Copiar')->count() > 0 ? $crawler->selectButton('Copiar')->form() : $crawler->filter('form')->last()->form();
        $form['activity_copy_from_wlt[project]']->select((string) $this->em()->getConnection()->fetchOne('SELECT id FROM wlt_project WHERE organization_id = ? ORDER BY id LIMIT 1', [$this->getOrganization()->getId()]));
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(['A1.1', 'A1.2', 'A2.1', 'A2.2'], $this->activityCodes($target));
    }

    public function testImportCreatesNewActivitiesAndUpdatesExistingOnes(): void
    {
        $shiftId = $this->shiftId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/fct/actividad/importar/$shiftId", ['data' => "A1: Descripción cambiada\nZ9: Actividad nueva\nlínea sin formato\n"]);

        self::assertResponseRedirects();
        self::assertSame(['A1', 'A2', 'A3', 'Z9'], $this->activityCodes($shiftId));
        self::assertSame('Descripción cambiada', $this->em()->find(Activity::class, $this->activityId($shiftId, 'A1'))->getDescription());

        $this->client->request('POST', "/fct/actividad/importar/$shiftId", ['data' => '   ']);
        self::assertResponseRedirects();
        self::assertSame(['A1', 'A2', 'A3', 'Z9'], $this->activityCodes($shiftId));
    }

    /**
     * @dataProvider nonManagerProvider
     */
    public function testOthersCannotImportOrCopyActivities(string $username): void
    {
        $shiftId = $this->shiftId();
        $target = $this->createEmptyShift();
        $this->login($this->getUser($username));

        foreach (["/fct/actividad/copiar/$target", "/fct/actividad/copiar/dual/$target"] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403, "$username $path");
        }
        $this->client->request('POST', "/fct/actividad/importar/$shiftId", ['data' => 'Q1: Intrusa']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame(['A1', 'A2', 'A3'], $this->activityCodes($shiftId));
    }

    public function testAdministratorOfAnotherCentreCannotImportOrCopy(): void
    {
        $shiftId = $this->shiftId();
        $target = $this->createEmptyShift();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach (["/fct/actividad/copiar/$target", "/fct/actividad/copiar/dual/$target"] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', "/fct/actividad/importar/$shiftId", ['data' => 'Q1: Intrusa']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame(['A1', 'A2', 'A3'], $this->activityCodes($shiftId));
    }
}
