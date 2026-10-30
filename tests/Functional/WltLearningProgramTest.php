<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Programas formativos de las empresas de un proyecto de FP dual y las concreciones de sus actividades: listado,
 * alta, edición y borrado. Los gestiona quien puede gestionar el proyecto, y nunca se tocan elementos de otro
 * proyecto o de otro centro aunque se envíen sus identificadores.
 */
class WltLearningProgramTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function projectId(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) $this->connection()->fetchOne('SELECT id FROM wlt_project WHERE organization_id = ? ORDER BY id LIMIT 1', [$this->getOrganization($code)->getId()]);
    }

    private function programId(int $project): int
    {
        return (int) $this->connection()->fetchOne('SELECT id FROM wlt_learning_program WHERE project_id = ? ORDER BY id LIMIT 1', [$project]);
    }

    private function activityId(int $project): int
    {
        return (int) $this->connection()->fetchOne('SELECT id FROM wlt_activity WHERE project_id = ? ORDER BY code LIMIT 1', [$project]);
    }

    private function realizationId(int $activity): int
    {
        return (int) $this->connection()->fetchOne('SELECT id FROM wlt_activity_realization WHERE activity_id = ? ORDER BY code LIMIT 1', [$activity]);
    }

    private function rows(string $table, string $where = '1', array $params = []): int
    {
        return (int) $this->connection()->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
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
        $program = $this->programId($project);
        $activity = $this->activityId($project);
        $realization = $this->realizationId($activity);
        $this->login($this->getUser($username));

        foreach ([
            "/dual/programa/$program",
            "/dual/programa/$project/listar/1",
            "/dual/proyecto/programa/$activity/concrecion/1",
            "/dual/proyecto/programa/$activity/concrecion/nueva",
            "/dual/proyecto/programa/$realization/detalles/concrecion",
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($username . ' ' . $url);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['tutor', 'profesor', 'tutorlaboral', 'alumno5', 'gestorffe'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotSeeOrChangeThem(string $username): void
    {
        $project = $this->projectId();
        $program = $this->programId($project);
        $activity = $this->activityId($project);
        $realization = $this->realizationId($activity);
        $programs = $this->rows('wlt_learning_program');
        $realizations = $this->rows('wlt_activity_realization');
        $this->login($this->getUser($username));

        foreach ([
            "/dual/programa/$program",
            "/dual/programa/nuevo/$project",
            "/dual/proyecto/programa/$activity/concrecion/1",
            "/dual/proyecto/programa/$activity/concrecion/nueva",
            "/dual/proyecto/programa/$realization/detalles/concrecion",
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $username . ' ' . $url);
        }
        $this->client->request('POST', "/dual/programa/operacion/$project", ['items' => [$program], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', "/dual/proyecto/programa/$activity/eliminar/concrecion", ['items' => [$realization], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($programs, $this->rows('wlt_learning_program'));
        self::assertSame($realizations, $this->rows('wlt_activity_realization'));
    }

    public function testAdministratorOfAnotherCentreCannotUseThem(): void
    {
        $project = $this->projectId();
        $program = $this->programId($project);
        $activity = $this->activityId($project);
        $realization = $this->realizationId($activity);
        $programs = $this->rows('wlt_learning_program');
        $realizations = $this->rows('wlt_activity_realization');
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach (["/dual/programa/$program", "/dual/proyecto/programa/$activity/concrecion/1", "/dual/proyecto/programa/$realization/detalles/concrecion"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "/dual/programa/operacion/$project", ['items' => [$program], 'confirm' => 'ok']);
        $this->client->request('POST', "/dual/proyecto/programa/$activity/eliminar/concrecion", ['items' => [$realization], 'confirm' => 'ok']);

        self::assertSame($programs, $this->rows('wlt_learning_program'));
        self::assertSame($realizations, $this->rows('wlt_activity_realization'));
    }

    public function testListOfProgramsFiltersBySearch(): void
    {
        $project = $this->projectId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', "/dual/programa/$project/listar/1", ['q' => 'Tecnologías del Sur']);
        self::assertStringContainsString('Tecnologías del Sur', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', "/dual/programa/$project/listar/1", ['q' => 'zzzz-no-existe']);
        self::assertStringNotContainsString('Tecnologías del Sur', (string) $this->client->getResponse()->getContent());
    }

    public function testProgramIsCreatedEditedAndDeleted(): void
    {
        $project = $this->projectId();
        $company = (int) $this->connection()->fetchOne('SELECT id FROM company WHERE id NOT IN (SELECT company_id FROM wlt_learning_program WHERE project_id = ?) ORDER BY id LIMIT 1', [$project]);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->rows('wlt_learning_program', 'project_id = ?', [$project]);

        $crawler = $this->client->request('GET', "/dual/programa/nuevo/$project");
        $form = $crawler->selectButton('Guardar cambios')->form();
        $form['learning_program[company]']->select((string) $company);
        $this->client->submit($form);
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSame($before + 1, $this->rows('wlt_learning_program', 'project_id = ?', [$project]));

        $id = (int) $this->connection()->fetchOne('SELECT id FROM wlt_learning_program WHERE project_id = ? AND company_id = ?', [$project, $company]);
        $activity = $this->activityId($project);
        $realization = $this->realizationId($activity);
        $crawler = $this->client->request('GET', "/dual/programa/$id");
        $form = $crawler->selectButton('Guardar cambios')->form();
        $form['learning_program[activityRealizations]'][0]->tick();
        $this->client->submit($form);
        self::assertResponseRedirects();
        self::assertGreaterThan(0, $this->rows('wlt_learning_program_activity_realization', 'learning_program_id = ?', [$id]));

        // sin confirmar solo se pide confirmación
        $this->client->request('POST', "/dual/programa/operacion/$project", ['items' => [$id], 'delete' => '']);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->rows('wlt_learning_program', 'id = ?', [$id]));

        $this->client->request('POST', "/dual/programa/operacion/$project", ['items' => [$id], 'delete' => '', 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame(0, $this->rows('wlt_learning_program', 'id = ?', [$id]));
        self::assertSame(1, $this->rows('wlt_activity_realization', 'id = ?', [$realization]), 'Las concreciones de actividad se conservan');
    }

    public function testProgramsOfAnotherProjectAreNotDeletedThroughThisOne(): void
    {
        $project = $this->projectId();
        $foreign = $this->programId($this->projectId(DemoData::ORG_B_CODE));
        $before = $this->rows('wlt_learning_program');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/dual/programa/operacion/$project", ['items' => [$foreign], 'delete' => '', 'confirm' => 'ok']);

        self::assertSame($before, $this->rows('wlt_learning_program'));
    }

    public function testRealizationIsCreatedEditedAndDeleted(): void
    {
        $project = $this->projectId();
        $activity = $this->activityId($project);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->rows('wlt_activity_realization', 'activity_id = ?', [$activity]);

        $crawler = $this->client->request('GET', "/dual/proyecto/programa/$activity/concrecion/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'activity_realization[code]' => 'ZZ.1',
            'activity_realization[description]' => 'Concreción de prueba',
        ]));
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSame($before + 1, $this->rows('wlt_activity_realization', 'activity_id = ?', [$activity]));

        $id = (int) $this->connection()->fetchOne('SELECT id FROM wlt_activity_realization WHERE activity_id = ? AND code = ?', [$activity, 'ZZ.1']);
        $crawler = $this->client->request('GET', "/dual/proyecto/programa/$id/detalles/concrecion");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['activity_realization[description]' => 'Concreción editada']));
        self::assertResponseRedirects();
        self::assertSame('Concreción editada', $this->connection()->fetchOne('SELECT description FROM wlt_activity_realization WHERE id = ?', [$id]));

        $this->client->request('GET', "/dual/proyecto/programa/$activity/concrecion/1", ['q' => 'ZZ.1']);
        self::assertStringContainsString('Concreción editada', (string) $this->client->getResponse()->getContent());

        $this->client->request('POST', "/dual/proyecto/programa/$activity/eliminar/concrecion", ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->rows('wlt_activity_realization', 'id = ?', [$id]));

        $this->client->request('POST', "/dual/proyecto/programa/$activity/eliminar/concrecion", ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame(0, $this->rows('wlt_activity_realization', 'id = ?', [$id]));
    }

    public function testRealizationWithoutCodeOrDuplicatedIsRejected(): void
    {
        $project = $this->projectId();
        $activity = $this->activityId($project);
        $existing = (string) $this->connection()->fetchOne('SELECT code FROM wlt_activity_realization WHERE activity_id = ? ORDER BY code LIMIT 1', [$activity]);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->rows('wlt_activity_realization');

        $crawler = $this->client->request('GET', "/dual/proyecto/programa/$activity/concrecion/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['activity_realization[code]' => '', 'activity_realization[description]' => 'x']));
        self::assertResponseStatusCodeSame(200);

        $crawler = $this->client->request('GET', "/dual/proyecto/programa/$activity/concrecion/nueva");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['activity_realization[code]' => $existing, 'activity_realization[description]' => 'x']));
        self::assertResponseStatusCodeSame(200);

        self::assertSame($before, $this->rows('wlt_activity_realization'));
    }

    public function testRealizationsOfAnotherActivityAreNotDeletedThroughThisOne(): void
    {
        $project = $this->projectId();
        $activity = $this->activityId($project);
        $other = (int) $this->connection()->fetchOne('SELECT id FROM wlt_activity WHERE project_id = ? AND id <> ? ORDER BY id LIMIT 1', [$project, $activity]);
        $foreign = $this->realizationId($other);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/dual/proyecto/programa/$activity/eliminar/concrecion", ['items' => [$foreign], 'confirm' => 'ok']);

        self::assertSame(1, $this->rows('wlt_activity_realization', 'id = ?', [$foreign]));
    }
}
