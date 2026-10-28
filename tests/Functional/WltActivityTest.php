<?php

namespace App\Tests\Functional;

use App\Entity\WltModule\Activity;
use App\Entity\WltModule\Project;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Actividades del programa formativo de un proyecto de FP dual: listado, alta, edición, borrado y copia.
 * El proyecto de las fixtures tiene las actividades A1 y A2, con realizaciones asignadas a los acuerdos.
 */
class WltActivityTest extends AppWebTestCase
{
    private const BASE = '/dual/proyecto/programa';

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function projectId(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->em()->getRepository(Project::class)->findOneBy(['organization' => $this->getOrganization($code)])->getId();
    }

    /**
     * @return list<string>
     */
    private function codes(int $projectId): array
    {
        return array_map(
            static fn(Activity $a): string => $a->getCode(),
            $this->em()->getRepository(Activity::class)->findBy(['project' => $projectId], ['code' => 'ASC'])
        );
    }

    private function activityId(int $projectId, string $code): int
    {
        return $this->em()->getRepository(Activity::class)->findOneBy(['project' => $projectId, 'code' => $code])->getId();
    }

    /**
     * @return list<string>
     */
    private function readPaths(int $project, int $activity): array
    {
        return [
            self::BASE . "/$project/actividad/1",
            self::BASE . "/$project/actividad/nueva",
            self::BASE . "/actividad/$activity",
            "/dual/proyecto/copiar/$project",
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'coordinador' => ['alfa.coordinador'];
        yield 'jefe de departamento' => ['alfa.jefe'];
    }

    /**
     * @dataProvider managerProvider
     */
    public function testManagersOpenTheScreens(string $username): void
    {
        $project = $this->projectId();
        $activity = $this->activityId($project, 'A1');
        $this->login($this->getUser($username));

        foreach ($this->readPaths($project, $activity) as $path) {
            $this->client->request('GET', $path);
            self::assertResponseIsSuccessful($path);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['tutor', 'profesor', 'tutorlaboral', 'alumno5', 'alumno1', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotSeeOrChangeActivities(string $username): void
    {
        $project = $this->projectId();
        $activity = $this->activityId($project, 'A1');
        $this->login($this->getUser($username));

        foreach ($this->readPaths($project, $activity) as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', self::BASE . "/$project/actividad/eliminar", ['items' => [$activity], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame(['A1', 'A2'], $this->codes($project));
    }

    public function testAdministratorOfAnotherOrganizationCannotUseThem(): void
    {
        $project = $this->projectId();
        $activity = $this->activityId($project, 'A1');
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ($this->readPaths($project, $activity) as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', self::BASE . "/$project/actividad/eliminar", ['items' => [$activity], 'confirm' => 'ok']);

        self::assertSame(['A1', 'A2'], $this->codes($project));
    }

    public function testManagerCreatesAndEditsAnActivity(): void
    {
        $project = $this->projectId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', self::BASE . "/$project/actividad/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'activity[code]' => 'A3', 'activity[description]' => 'Despliegue de aplicaciones',
        ]));
        self::assertResponseRedirects();
        self::assertSame(['A1', 'A2', 'A3'], $this->codes($project));

        $id = $this->activityId($project, 'A3');
        $crawler = $this->client->request('GET', self::BASE . "/actividad/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['activity[description]' => 'Despliegue corregido']));
        self::assertResponseRedirects();
        self::assertSame('Despliegue corregido', $this->em()->getRepository(Activity::class)->find($id)->getDescription());
    }

    public function testActivityWithoutCodeOrDescriptionIsRejected(): void
    {
        $project = $this->projectId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', self::BASE . "/$project/actividad/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['activity[code]' => '', 'activity[description]' => '']));

        self::assertResponseStatusCodeSame(200);
        self::assertSame(['A1', 'A2'], $this->codes($project));
    }

    public function testDeletionRequiresConfirmation(): void
    {
        $project = $this->projectId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $crawler = $this->client->request('GET', self::BASE . "/$project/actividad/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['activity[code]' => 'A3', 'activity[description]' => 'Para borrar']));
        $id = $this->activityId($project, 'A3');

        $this->client->request('POST', self::BASE . "/$project/actividad/eliminar", ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertContains('A3', $this->codes($project));

        $this->client->request('POST', self::BASE . "/$project/actividad/eliminar", ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame(['A1', 'A2'], $this->codes($project));
    }

    public function testActivityWithRealizationsInUseIsKeptAndTheErrorIsReported(): void
    {
        $project = $this->projectId();
        $id = $this->activityId($project, 'A2');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        // sus realizaciones están asignadas a los acuerdos de las fixtures: la base de datos impide borrarla
        $this->client->request('POST', self::BASE . "/$project/actividad/eliminar", ['items' => [$id], 'confirm' => 'ok']);

        self::assertResponseRedirects();
        self::assertSame(['A1', 'A2'], $this->codes($project), 'La actividad en uso se conserva');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.alert-danger, .alert-error', 'Se avisa de que no se ha podido borrar');
    }

    public function testActivitiesOfAnotherProjectCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->projectId();
        $foreign = $this->projectId(DemoData::ORG_B_CODE);
        $foreignActivity = $this->activityId($foreign, 'A1');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', self::BASE . "/$own/actividad/eliminar", ['items' => [$foreignActivity], 'confirm' => 'ok']);

        self::assertSame(['A1', 'A2'], $this->codes($foreign));
    }
}
