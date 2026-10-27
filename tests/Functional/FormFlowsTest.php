<?php

namespace App\Tests\Functional;

use App\Entity\Company;
use App\Entity\Edu\Department;
use App\Entity\Edu\Group;
use App\Entity\Person;
use App\Tests\Fixtures\DemoData;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Flujos de escritura de extremo a extremo (crear, modificar y eliminar) con los formularios reales,
 * incluido el token CSRF. Cada test se revierte al terminar.
 */
class FormFlowsTest extends AppWebTestCase
{
    private function url(string $route, array $parameters = []): string
    {
        return static::getContainer()->get('router')->generate($route, $parameters);
    }

    private function em(): \Doctrine\ORM\EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function academicYearId(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->getOrganization($code)->getCurrentAcademicYear()->getId();
    }

    private function departmentNamed(string $name): ?Department
    {
        return $this->em()->getRepository(Department::class)->findOneBy(['name' => $name]);
    }

    private function submit(Crawler $crawler, string $button, array $values): Crawler
    {
        return $this->client->submit($crawler->selectButton($button)->form($values));
    }

    // --- Usuarios (administrador global) -------------------------------------------------------

    public function testAdministratorCreatesEditsAndDeletesAUser(): void
    {
        $this->login($this->getAdmin());

        $crawler = $this->client->request('GET', $this->url('admin_user_form_new'));
        $this->submit($crawler, 'Guardar cambios', [
            'person[loginUsername]' => 'usuario.nuevo',
            'person[firstName]' => 'Nuevo',
            'person[lastName]' => 'Usuario de Prueba',
            'person[emailAddress]' => 'usuario.nuevo@example.com',
            'person[gender]' => '1',
            'person[enabled]' => '1',
            'person[newPassword][first]' => 'ClaveSegura-2026',
            'person[newPassword][second]' => 'ClaveSegura-2026',
        ]);
        self::assertResponseRedirects(null, null, 'El alta del usuario debería redirigir al listado');

        $person = $this->em()->getRepository(Person::class)->findOneBy(['loginUsername' => 'usuario.nuevo']);
        self::assertInstanceOf(Person::class, $person);
        self::assertSame('Nuevo', $person->getFirstName());
        self::assertTrue($person->isEnabled());
        self::assertFalse($person->isGlobalAdministrator());
        // la contraseña se guarda cifrada, nunca en claro
        self::assertNotSame('ClaveSegura-2026', $person->getPassword());
        self::assertTrue(static::getContainer()->get('security.user_password_hasher')->isPasswordValid($person, 'ClaveSegura-2026'));

        // aparece en el listado
        $this->client->request('GET', $this->url('admin_user_list'), ['q' => 'usuario.nuevo']);
        self::assertStringContainsString('usuario.nuevo@example.com', (string) $this->client->getResponse()->getContent());

        // modificar
        $crawler = $this->client->request('GET', $this->url('admin_user_form_edit', ['id' => $person->getId()]));
        $this->submit($crawler, 'Guardar cambios', ['person[lastName]' => 'Apellido Modificado']);
        self::assertResponseRedirects();
        self::assertSame('Apellido Modificado', $this->em()->find(Person::class, $person->getId())->getLastName());

        // eliminar
        $this->client->request('POST', $this->url('admin_user_delete'), ['users' => [$person->getId()], 'confirm' => 'ok']);
        self::assertNull($this->em()->find(Person::class, $person->getId()));
    }

    public function testAdministratorCannotDeleteThemselves(): void
    {
        $admin = $this->getAdmin();
        $other = $this->getUser(DemoData::USER_A_TEACHER);
        $this->login($admin);

        // la pantalla de confirmación no ofrece borrar al usuario actual...
        $crawler = $this->client->request('POST', $this->url('admin_user_delete'), ['users' => [$admin->getId(), $other->getId()]]);
        self::assertResponseIsSuccessful();
        $offered = $crawler->filter('input[name="users[]"]')->each(fn (Crawler $input): int => (int) $input->attr('value'));
        self::assertSame([$other->getId()], $offered);

        // ...y una petición directa tampoco debe poder hacerlo
        $this->client->request('POST', $this->url('admin_user_delete'), ['users' => [$admin->getId()], 'confirm' => 'ok']);
        self::assertNotNull($this->em()->find(Person::class, $admin->getId()), 'El administrador ha podido borrarse a sí mismo');
    }

    public function testUserCreationIsRejectedWhenRequiredDataIsMissing(): void
    {
        $this->login($this->getAdmin());
        $before = $this->em()->getRepository(Person::class)->count([]);

        $crawler = $this->client->request('GET', $this->url('admin_user_form_new'));
        $this->submit($crawler, 'Guardar cambios', [
            'person[loginUsername]' => 'sin.nombre',
            'person[firstName]' => '',
            'person[lastName]' => '',
            'person[gender]' => '1',
            'person[enabled]' => '1',
            'person[newPassword][first]' => 'ClaveSegura-2026',
            'person[newPassword][second]' => 'ClaveSegura-2026',
        ]);

        // el formulario se vuelve a mostrar con errores y no se crea nada
        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->em()->getRepository(Person::class)->count([]));
    }

    public function testUserCreationIsRejectedWhenPasswordsDoNotMatch(): void
    {
        $this->login($this->getAdmin());

        $crawler = $this->client->request('GET', $this->url('admin_user_form_new'));
        $this->submit($crawler, 'Guardar cambios', [
            'person[loginUsername]' => 'clave.distinta',
            'person[firstName]' => 'Clave',
            'person[lastName]' => 'Distinta',
            'person[gender]' => '1',
            'person[enabled]' => '1',
            'person[newPassword][first]' => 'ClaveSegura-2026',
            'person[newPassword][second]' => 'OtraClave-2026',
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertNull($this->em()->getRepository(Person::class)->findOneBy(['loginUsername' => 'clave.distinta']));
    }

    public function testUsernameMustBeUnique(): void
    {
        $this->login($this->getAdmin());
        $before = $this->em()->getRepository(Person::class)->count([]);

        $crawler = $this->client->request('GET', $this->url('admin_user_form_new'));
        $this->submit($crawler, 'Guardar cambios', [
            'person[loginUsername]' => DemoData::USER_A_TEACHER,
            'person[firstName]' => 'Duplicado',
            'person[lastName]' => 'Duplicado',
            'person[gender]' => '1',
            'person[enabled]' => '1',
            'person[newPassword][first]' => 'ClaveSegura-2026',
            'person[newPassword][second]' => 'ClaveSegura-2026',
        ]);

        self::assertSame($before, $this->em()->getRepository(Person::class)->count([]));
    }

    // --- Estructura académica (administrador local) --------------------------------------------

    public function testLocalAdministratorCreatesEditsAndDeletesADepartment(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $academicYear = $this->academicYearId();

        $crawler = $this->client->request('GET', $this->url('organization_department_new', ['academicYear' => $academicYear]));
        // el código interno solo lo puede modificar el administrador global: el campo está deshabilitado
        self::assertTrue($crawler->filter('[name="department[internalCode]"][disabled]')->count() === 1);
        $this->submit($crawler, 'Guardar cambios', ['department[name]' => 'Matemáticas']);
        self::assertResponseRedirects();

        $department = $this->departmentNamed('Matemáticas');
        self::assertInstanceOf(Department::class, $department);
        self::assertNull($department->getInternalCode());
        // se crea en la organización de quien lo da de alta
        self::assertSame($this->getOrganization()->getId(), $department->getAcademicYear()->getOrganization()->getId());

        $crawler = $this->client->request('GET', $this->url('organization_department_edit', ['id' => $department->getId()]));
        $this->submit($crawler, 'Guardar cambios', ['department[name]' => 'Matemáticas y Ciencias']);
        self::assertSame('Matemáticas y Ciencias', $this->em()->find(Department::class, $department->getId())->getName());

        $this->client->request('POST', $this->url('organization_department_delete', ['academicYear' => $academicYear]), [
            'items' => [$department->getId()],
            'confirm' => 'ok',
        ]);
        self::assertNull($this->em()->find(Department::class, $department->getId()));
    }

    public function testDepartmentNameIsRequired(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->em()->getRepository(Department::class)->count([]);

        $crawler = $this->client->request('GET', $this->url('organization_department_new', ['academicYear' => $this->academicYearId()]));
        $this->submit($crawler, 'Guardar cambios', ['department[name]' => '']);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->em()->getRepository(Department::class)->count([]));
    }

    public function testLocalAdministratorCreatesAGroup(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', $this->url('organization_group_new', ['academicYear' => $this->academicYearId()]));
        $form = $crawler->selectButton('Guardar cambios')->form();
        $grade = array_values(array_filter(
            array_keys($form['group[grade]']->availableOptionValues() ? array_flip($form['group[grade]']->availableOptionValues()) : []),
            fn ($value) => $value !== ''
        ))[0];
        $this->client->submit($form, ['group[grade]' => (string) $grade, 'group[name]' => 'Grupo nuevo', 'group[internalCode]' => 'NUEVO']);
        self::assertResponseRedirects();

        $group = $this->em()->getRepository(Group::class)->findOneBy(['name' => 'Grupo nuevo']);
        self::assertInstanceOf(Group::class, $group);
        self::assertSame($this->getOrganization()->getId(), $group->getGrade()->getTraining()->getAcademicYear()->getOrganization()->getId());
    }

    // --- Autorización de las escrituras --------------------------------------------------------

    public function testOrdinaryTeacherCannotCreateADepartmentEvenPostingDirectly(): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER));
        $before = $this->em()->getRepository(Department::class)->count([]);

        $this->client->request('POST', $this->url('organization_department_new', ['academicYear' => $this->academicYearId()]), [
            'department' => ['name' => 'Intruso', 'internalCode' => 'X'],
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->em()->getRepository(Department::class)->count([]));
    }

    public function testOrganizationBAdministratorCannotEditAnOrganizationADepartment(): void
    {
        $department = $this->em()->getRepository(Department::class)->findOneBy(['name' => 'Informática'], ['id' => 'ASC']);
        $originalName = $department->getName();

        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->client->request('POST', $this->url('organization_department_edit', ['id' => $department->getId()]), [
            'department' => ['name' => 'Secuestrado', 'internalCode' => 'HACK'],
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($originalName, $this->em()->find(Department::class, $department->getId())->getName());
    }

    public function testPostWithoutCsrfTokenDoesNotCreateAnything(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->em()->getRepository(Department::class)->count([]);

        // petición hecha a mano, sin el token CSRF del formulario
        $this->client->request('POST', $this->url('organization_department_new', ['academicYear' => $this->academicYearId()]), [
            'department' => ['name' => 'Sin token', 'internalCode' => 'CSRF'],
        ]);

        self::assertSame($before, $this->em()->getRepository(Department::class)->count([]));
        self::assertNull($this->departmentNamed('Sin token'));
    }

    public function testEmptyingARequiredFieldWhenEditingShowsAnErrorAndKeepsTheData(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $department = $this->em()->getRepository(Department::class)->findOneBy(['name' => 'Informática'], ['id' => 'ASC']);
        $id = $department->getId();

        $crawler = $this->client->request('GET', $this->url('organization_department_edit', ['id' => $id]));
        $crawler = $this->submit($crawler, 'Guardar cambios', ['department[name]' => '']);

        // antes de TypeErrorTolerantDataMapper esto era un error 500 ("null given" en una propiedad string)
        self::assertResponseStatusCodeSame(200);
        self::assertGreaterThan(0, $crawler->filter('.invalid-feedback, .form-error-message, .help-block, .has-error')->count(), 'No se muestra el error');
        self::assertSame('Informática', $this->em()->find(Department::class, $id)->getName());
    }

    public function testUserWhoIsNotATeacherCannotCreateAMeeting(): void
    {
        // el administrador local no es profesor del curso: no puede ser el creador de la reunión
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('GET', $this->url('work_linked_training_meeting_new', ['academicYear' => $this->academicYearId()]));

        self::assertResponseStatusCodeSame(403);
    }

    // --- Empresas -------------------------------------------------------------------------------

    public function testCompanyIsCreatedAndRequiresItsMandatoryFields(): void
    {
        $this->login($this->getAdmin());

        $crawler = $this->client->request('GET', $this->url('company_new'));
        $this->submit($crawler, 'Guardar cambios', [
            'company[code]' => 'B99999999',
            'company[name]' => 'Empresa de Prueba S.L.',
            'company[city]' => 'Linares',
        ]);
        self::assertResponseRedirects();
        $company = $this->em()->getRepository(Company::class)->findOneBy(['code' => 'B99999999']);
        self::assertInstanceOf(Company::class, $company);
        self::assertSame('Empresa de Prueba S.L.', $company->getName());

        // el CIF es único
        $before = $this->em()->getRepository(Company::class)->count([]);
        $crawler = $this->client->request('GET', $this->url('company_new'));
        $this->submit($crawler, 'Guardar cambios', [
            'company[code]' => 'B99999999',
            'company[name]' => 'Otra con el mismo CIF',
            'company[city]' => 'Úbeda',
        ]);
        self::assertSame($before, $this->em()->getRepository(Company::class)->count([]));

        // los datos obligatorios
        $crawler = $this->client->request('GET', $this->url('company_new'));
        $this->submit($crawler, 'Guardar cambios', ['company[code]' => '', 'company[name]' => '', 'company[city]' => '']);
        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->em()->getRepository(Company::class)->count([]));
    }
}
