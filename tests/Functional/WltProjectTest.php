<?php

namespace App\Tests\Functional;

use App\Entity\WltModule\Project;
use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Proyectos de FP dual: listado con búsqueda, alta, edición, matrícula de estudiantes y borrado. Los gestiona quien
 * administra el centro, el coordinador del proyecto y el jefe del departamento de sus grupos; el resto del
 * coordinadores de FP dual puede entrar al listado pero no tocar proyectos ajenos.
 */
class WltProjectTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

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

    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    private function groupId(): int
    {
        return $this->em()->getRepository(Project::class)->find($this->projectId())->getGroups()->first()->getId();
    }

    private function projectCount(): int
    {
        return (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wlt_project');
    }

    /**
     * Crea un proyecto con el formulario real, gestionado por la persona indicada, y devuelve su identificador
     */
    private function createProject(string $name, ?string $managerUsername = 'alfa.profesor'): int
    {
        $crawler = $this->client->request('GET', '/dual/proyecto/nuevo');
        self::assertResponseIsSuccessful();
        $values = ['project[name]' => $name];
        $form = $crawler->selectButton('Guardar cambios')->form($values);
        $form['project[groups]']->select([(string) $this->groupId()]);
        if (null !== $managerUsername && $form->has('project[manager]') && !$form['project[manager]']->isDisabled()) {
            $form['project[manager]']->select((string) $this->getUser($managerUsername)->getId());
        }
        $this->client->submit($form);
        self::assertResponseRedirects();

        return (int) $this->connection()->fetchOne('SELECT id FROM wlt_project WHERE name = ?', [$name]);
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
        $this->login($this->getUser($username));

        foreach ([
            '/dual/proyecto/listar/' . $this->year(),
            '/dual/proyecto/nuevo',
            "/dual/proyecto/$project",
            "/dual/proyecto/estudiantes/$project",
            "/dual/programa/$project/listar/1",
            "/dual/programa/nuevo/$project",
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
        foreach (['tutor', 'profesor', 'tutorlaboral', 'alumno5', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotSeeOrChangeProjects(string $username): void
    {
        $project = $this->projectId();
        $before = $this->projectCount();
        $this->login($this->getUser($username));

        foreach ([
            '/dual/proyecto/listar/' . $this->year(),
            '/dual/proyecto/nuevo',
            "/dual/proyecto/$project",
            "/dual/proyecto/estudiantes/$project",
            "/dual/programa/$project/listar/1",
            "/dual/programa/nuevo/$project",
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $username . ' ' . $url);
        }
        $this->client->request('POST', '/dual/proyecto/eliminar', ['items' => [$project], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->projectCount());
    }

    public function testAdministratorOfAnotherCentreCannotUseTheProject(): void
    {
        $project = $this->projectId();
        $before = $this->projectCount();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach (["/dual/proyecto/$project", "/dual/proyecto/estudiantes/$project", "/dual/programa/$project/listar/1", "/dual/programa/nuevo/$project"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', '/dual/proyecto/eliminar', ['items' => [$project], 'confirm' => 'ok']);

        self::assertSame($before, $this->projectCount(), 'No se borra un proyecto de otro centro');
    }

    public function testListFiltersBySearch(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', '/dual/proyecto/listar/' . $this->year(), ['q' => 'Desarrollo de Aplicaciones']);
        self::assertStringContainsString('Proyecto de FP dual en Desarrollo', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/dual/proyecto/listar/' . $this->year(), ['q' => 'zzzz-no-existe']);
        self::assertStringNotContainsString('Proyecto de FP dual en Desarrollo', (string) $this->client->getResponse()->getContent());
    }

    public function testAdministratorCreatesAndEditsAProject(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->projectCount();

        $id = $this->createProject('Zz proyecto');

        self::assertSame($before + 1, $this->projectCount());
        self::assertSame((int) $this->getUser('alfa.profesor')->getId(), (int) $this->connection()->fetchOne('SELECT manager_id FROM wlt_project WHERE id = ?', [$id]));
        $crawler = $this->client->request('GET', "/dual/proyecto/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['project[name]' => 'Zz proyecto editado']));
        self::assertResponseRedirects();
        self::assertSame('Zz proyecto editado', $this->connection()->fetchOne('SELECT name FROM wlt_project WHERE id = ?', [$id]));
    }

    public function testProjectWithoutNameOrGroupsIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->projectCount();

        $crawler = $this->client->request('GET', '/dual/proyecto/nuevo');
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['project[name]' => '']));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->projectCount());
    }

    public function testNonAdministratorCreatesProjectsManagedByThemselves(): void
    {
        $this->login($this->getUser('alfa.coordinador'));

        $id = $this->createProject('Zz proyecto del coordinador', null);

        self::assertSame(
            (int) $this->getUser('alfa.coordinador')->getId(),
            (int) $this->connection()->fetchOne('SELECT manager_id FROM wlt_project WHERE id = ?', [$id])
        );
    }

    public function testCoordinatorCannotManageTheProjectOfAnotherTeacher(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $foreign = $this->createProject('Zz proyecto ajeno');

        // gestiona otro proyecto del centro y por eso entra al módulo, pero este no es suyo ni de su departamento
        $this->login($this->getUser('alfa.coordinador'));
        foreach (["/dual/proyecto/$foreign", "/dual/proyecto/estudiantes/$foreign", "/dual/programa/$foreign/listar/1"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', '/dual/proyecto/estudiantes/' . $foreign, []);
        self::assertResponseStatusCodeSame(403);

        $before = $this->projectCount();
        $this->client->request('POST', '/dual/proyecto/eliminar', ['items' => [$foreign], 'confirm' => 'ok']);
        self::assertSame($before, $this->projectCount(), 'Solo se borran proyectos propios');
    }

    public function testStudentEnrollmentsAreSavedByTheProjectManager(): void
    {
        $this->login($this->getUser('alfa.coordinador'));
        $project = $this->projectId();

        $crawler = $this->client->request('GET', "/dual/proyecto/estudiantes/$project");
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form());

        self::assertResponseRedirects();
    }

    public function testDeletionRequiresConfirmationAndRemovesTheProject(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $id = $this->createProject('Zz proyecto a borrar');

        $this->client->request('POST', '/dual/proyecto/eliminar', ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wlt_project WHERE id = ?', [$id]));

        $this->client->request('POST', '/dual/proyecto/eliminar', ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame(0, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wlt_project WHERE id = ?', [$id]));
    }

    public function testDeletingTheProjectOfTheFixturesWithTrackedDataDoesNotFail(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        // tiene actividades, acuerdos, reuniones y seguimiento: se borra todo o se informa del error, nunca falla el servidor
        $this->client->request('POST', '/dual/proyecto/eliminar', ['items' => [$this->projectId()], 'confirm' => 'ok']);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }
}
