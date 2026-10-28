<?php

namespace App\Tests\Functional;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Entity\Role;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Operaciones destructivas de administración: borrado de organizaciones, de cursos académicos, cambio de
 * curso o de organización actual y asignación de roles. Cada test se revierte al terminar.
 */
class AdminDestructiveOperationsTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    /**
     * Crea un curso académico adicional (no actual) en la organización indicada
     */
    private function addAcademicYear(string $code, string $description): int
    {
        $em = $this->em();
        $organization = $em->getRepository(Organization::class)->findOneBy(['code' => $code]);
        $current = $organization->getCurrentAcademicYear();

        $academicYear = (new AcademicYear())
            ->setOrganization($organization)
            ->setDescription($description)
            ->setStartDate((clone $current->getStartDate())->modify('-1 year'))
            ->setEndDate((clone $current->getEndDate())->modify('-1 year'));
        $em->persist($academicYear);
        $em->flush();

        return $academicYear->getId();
    }

    private function academicYearExists(int $id): bool
    {
        return $this->em()->getRepository(AcademicYear::class)->find($id) instanceof AcademicYear;
    }

    private function currentAcademicYearId(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->em()->getRepository(Organization::class)->findOneBy(['code' => $code])->getCurrentAcademicYear()->getId();
    }

    private function organizationExists(string $code): bool
    {
        return $this->em()->getRepository(Organization::class)->findOneBy(['code' => $code]) instanceof Organization;
    }

    // --- Cursos académicos ---------------------------------------------------------------------

    public function testAcademicYearDeletionRequiresConfirmation(): void
    {
        $oldYear = $this->addAcademicYear(DemoData::ORG_A_CODE, '2020/2021');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        // sin confirmar solo se muestra la pantalla de confirmación
        $this->client->request('POST', '/centro/cursoacademico/operacion', ['items' => [$oldYear]]);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->academicYearExists($oldYear));

        $this->client->request('POST', '/centro/cursoacademico/operacion', ['items' => [$oldYear], 'confirm' => 'ok']);
        self::assertResponseRedirects('/centro/cursoacademico/listar');
        self::assertFalse($this->academicYearExists($oldYear));
    }

    public function testCurrentAcademicYearCannotBeDeleted(): void
    {
        $current = $this->currentAcademicYearId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', '/centro/cursoacademico/operacion', ['items' => [$current], 'confirm' => 'ok']);

        self::assertTrue($this->academicYearExists($current));
        self::assertSame($current, $this->currentAcademicYearId());
    }

    public function testAcademicYearOfAnotherOrganizationCannotBeDeleted(): void
    {
        $foreign = $this->addAcademicYear(DemoData::ORG_B_CODE, '2020/2021');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', '/centro/cursoacademico/operacion', ['items' => [$foreign], 'confirm' => 'ok']);

        self::assertTrue($this->academicYearExists($foreign));
    }

    public function testAcademicYearSwitchOnlyAcceptsOwnYears(): void
    {
        $own = $this->addAcademicYear(DemoData::ORG_A_CODE, '2020/2021');
        $foreign = $this->addAcademicYear(DemoData::ORG_B_CODE, '2020/2021');
        $original = $this->currentAcademicYearId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', '/centro/cursoacademico/operacion', ['switch' => $foreign]);
        self::assertSame($original, $this->currentAcademicYearId(), 'No se puede cambiar a un curso de otra organización');

        $this->client->request('POST', '/centro/cursoacademico/operacion', ['switch' => $own]);
        self::assertResponseRedirects('/centro/cursoacademico/listar');
        self::assertSame($own, $this->currentAcademicYearId());
    }

    /**
     * @dataProvider nonManagerProvider
     */
    public function testNonManagersCannotOperateOnAcademicYears(string $role): void
    {
        $oldYear = $this->addAcademicYear(DemoData::ORG_A_CODE, '2020/2021');
        $this->login($this->getUser(DemoData::username(DemoData::ORG_A, $role)));

        $this->client->request('POST', '/centro/cursoacademico/operacion', ['items' => [$oldYear], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/centro/cursoacademico/operacion', ['switch' => $oldYear]);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/centro/cursoacademico/copiar/' . $oldYear);
        self::assertResponseStatusCodeSame(403);

        self::assertTrue($this->academicYearExists($oldYear));
    }

    public function testAcademicYearOfAnotherOrganizationCannotBeCopied(): void
    {
        $foreign = $this->addAcademicYear(DemoData::ORG_B_CODE, '2020/2021');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', '/centro/cursoacademico/copiar/' . $foreign);

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonManagerProvider(): iterable
    {
        foreach ([
            DemoData::ROLE_DEPARTMENT_HEAD,
            DemoData::ROLE_GROUP_TUTOR,
            DemoData::ROLE_TEACHER,
            DemoData::ROLE_WORK_TUTOR,
            DemoData::ROLE_STUDENT_1,
            DemoData::ROLE_PROJECT_MANAGER,
            DemoData::ROLE_ITP_MANAGER,
        ] as $role) {
            yield $role => [$role];
        }
    }

    // --- Organizaciones (administrador global) -------------------------------------------------

    /**
     * @dataProvider organizationUserProvider
     */
    public function testOnlyGlobalAdministratorsCanOperateOnOrganizations(string $username): void
    {
        $this->login($this->getUser($username));

        $this->client->request('POST', '/admin/organizaciones/operacion', [
            'organizations' => [$this->getOrganization(DemoData::ORG_B_CODE)->getId()],
            'confirm' => 'ok',
        ]);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/admin/organizaciones/operacion', [
            'switch' => $this->getOrganization(DemoData::ORG_B_CODE)->getId(),
        ]);
        self::assertResponseStatusCodeSame(403);

        self::assertTrue($this->organizationExists(DemoData::ORG_B_CODE));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function organizationUserProvider(): iterable
    {
        yield 'administrador local' => [DemoData::USER_A_LOCAL_ADMIN];
        yield 'profesorado' => [DemoData::USER_A_TEACHER];
        yield 'administrador local de otra organización' => [DemoData::USER_B_LOCAL_ADMIN];
    }

    public function testAdministratorSwitchesOrganization(): void
    {
        $this->login($this->getAdmin());
        $other = $this->getOrganization(DemoData::ORG_B_CODE);

        $this->client->request('POST', '/admin/organizaciones/operacion', ['switch' => $other->getId()]);

        self::assertResponseRedirects('/admin/organizaciones/listar');
        self::assertSame($other->getId(), $this->client->getRequest()->getSession()->get('organization_id'));
    }

    public function testAdministratorSwitchToUnknownOrganizationChangesNothing(): void
    {
        $this->login($this->getAdmin());
        $original = $this->getOrganization()->getId();

        $this->client->request('POST', '/admin/organizaciones/operacion', ['switch' => 999999]);

        self::assertSame($original, $this->client->getRequest()->getSession()->get('organization_id'));
    }

    public function testOrganizationDeletionRequiresConfirmationAndKeepsTheCurrentOne(): void
    {
        $this->login($this->getAdmin());
        $current = $this->getOrganization(DemoData::ORG_A_CODE)->getId();
        $other = $this->getOrganization(DemoData::ORG_B_CODE)->getId();

        // sin confirmar no se borra nada
        $this->client->request('POST', '/admin/organizaciones/operacion', ['organizations' => [$current, $other]]);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->organizationExists(DemoData::ORG_B_CODE));

        // la organización actual nunca se borra, aunque se pida
        $this->client->request('POST', '/admin/organizaciones/operacion', ['organizations' => [$current], 'confirm' => 'ok']);
        self::assertTrue($this->organizationExists(DemoData::ORG_A_CODE));
    }

    public function testAdministratorDeletesAnotherOrganization(): void
    {
        $this->login($this->getAdmin());
        $other = $this->getOrganization(DemoData::ORG_B_CODE)->getId();

        $this->client->request('POST', '/admin/organizaciones/operacion', ['organizations' => [$other], 'confirm' => 'ok']);

        self::assertResponseRedirects('/admin/organizaciones/listar');
        // o se borra, o falla de forma controlada por los datos asociados; en ningún caso es un error 500
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    // --- Roles ---------------------------------------------------------------------------------

    /**
     * @dataProvider nonManagerProvider
     */
    public function testNonManagersCannotAssignRoles(string $role): void
    {
        $this->login($this->getUser(DemoData::username(DemoData::ORG_A, $role)));

        $this->client->request('GET', '/centro/rol');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/centro/rol', ['role_assign' => ['admin' => [$this->getUser(DemoData::USER_A_TEACHER)->getId()]]]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testRoleFormOnlyAcceptsTeachersOfTheOrganization(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $foreign = $this->getUser(DemoData::USER_B_TEACHER);
        $before = $this->em()->getRepository(Role::class)->count([]);

        $crawler = $this->client->request('GET', '/centro/rol');
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name=role_assign]')->form();
        // se manipula la petición para incluir a alguien que no pertenece al profesorado de la organización
        $values = $form->getPhpValues();
        $values['role_assign']['admin'] = [$foreign->getId()];
        $this->client->request('POST', '/centro/rol', $values);

        self::assertSame($before, $this->em()->getRepository(Role::class)->count([]));
        self::assertNull($this->em()->getRepository(Role::class)->findOneBy(['person' => $foreign->getId()]));
    }
}
