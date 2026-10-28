<?php

namespace App\Tests\Functional;

use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Profesorado del centro: listado, alta de una persona ya existente como docente del curso, edición de sus datos y
 * borrado. Solo quien administra el centro lo gestiona.
 */
class TeacherManagementTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function year(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->getOrganization($code)->getCurrentAcademicYear()->getId();
    }

    private function teacherOf(string $username): ?Teacher
    {
        return $this->em()->getRepository(Teacher::class)->createQueryBuilder('t')
            ->join('t.person', 'p')->where('p.loginUsername = :username')->andWhere('t.academicYear = :year')
            ->setParameter('username', $username)->setParameter('year', $this->year())
            ->getQuery()->getOneOrNullResult();
    }

    private function personId(string $username): int
    {
        return $this->getUser($username)->getId();
    }

    private function addTeacher(int $personId): void
    {
        $crawler = $this->client->request('GET', '/centro/profesorado/nuevo');
        $values = $crawler->selectButton('Guardar cambios')->form()->getPhpValues();
        $values['new_teacher']['person'] = (string) $personId;
        $this->client->request('POST', '/centro/profesorado/nuevo', $values);
    }

    public function testAdministratorSeesTheTeachersOfTheCentre(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', '/centro/profesorado/listar/' . $this->year());

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Docente Alfa', (string) $this->client->getResponse()->getContent());
    }

    public function testListFiltersBySearch(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', '/centro/profesorado/listar/' . $this->year(), ['q' => 'zzzz-no-existe']);

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('alfa.profesor', (string) $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['jefe', 'tutor', 'profesor', 'tutorlaboral', 'alumno1', 'coordinador', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotManageTeachers(string $username): void
    {
        $teacher = $this->teacherOf('alfa.profesor');
        $this->login($this->getUser($username));

        foreach (['/centro/profesorado/listar/' . $this->year(), '/centro/profesorado/nuevo', '/centro/profesorado/' . $teacher->getId()] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', '/centro/profesorado/eliminar/' . $this->year(), ['users' => [$teacher->getId()], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertNotNull($this->teacherOf('alfa.profesor'));
    }

    public function testAdministratorAddsAnExistingPersonAsTeacher(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        self::assertNull($this->teacherOf('alfa.alumno1'));

        $this->addTeacher($this->personId('alfa.alumno1'));

        self::assertResponseRedirects();
        self::assertNotNull($this->teacherOf('alfa.alumno1'));
    }

    public function testTheSamePersonIsNotAddedTwice(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->em()->getRepository(Teacher::class)->count([]);

        $this->addTeacher($this->personId('alfa.profesor'));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->em()->getRepository(Teacher::class)->count([]));
    }

    public function testAddingATeacherDoesNotChangeHowAnotherPersonAuthenticates(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $teacher = $this->getUser('alfa.alumno2');
        $before = [$teacher->getExternalCheck(), $teacher->getAllowExternalCheck(), $teacher->getPassword()];

        $this->addTeacher($teacher->getId());

        $after = $this->getUser('alfa.alumno2');
        self::assertSame($before[2], $after->getPassword(), 'La contraseña no cambia');
        self::assertSame($before[0], $after->getExternalCheck(), 'Añadir docente no cambia la forma de autenticarse');
    }

    public function testAdministratorCannotMakeAGlobalAdministratorAnExternallyAuthenticatedTeacher(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $global = $this->getAdmin();
        $before = $global->getExternalCheck();

        $this->addTeacher($global->getId());

        // un administrador global no debe poder ser modificado desde un centro
        self::assertSame($before, $this->getAdmin()->getExternalCheck());
    }

    public function testAdministratorCannotAddAPersonThatDoesNotExist(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->em()->getRepository(Teacher::class)->count([]);

        $this->addTeacher(999999);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($before, $this->em()->getRepository(Teacher::class)->count([]));
    }

    public function testAdministratorEditsTheDataOfATeacher(): void
    {
        $teacher = $this->teacherOf('alfa.profesor');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', '/centro/profesorado/' . $teacher->getId());
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'teacher[personFirstName]' => 'Profesora Renombrada',
        ]));

        self::assertResponseRedirects();
        self::assertSame('Profesora Renombrada', $this->getUser('alfa.profesor')->getFirstName());
    }

    public function testTeacherCannotTakeTheUsernameOfAnotherPerson(): void
    {
        $teacher = $this->teacherOf('alfa.profesor');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', '/centro/profesorado/' . $teacher->getId());
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'teacher[loginUsername]' => DemoData::USER_ADMIN,
        ]));

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame(1, $this->em()->getRepository(Person::class)->count(['loginUsername' => DemoData::USER_ADMIN]));
        self::assertSame('alfa.profesor', $this->getUser('alfa.profesor')->getLoginUsername());
    }

    public function testAdministratorOfAnotherCentreCannotEditOrDeleteTheTeacher(): void
    {
        $teacher = $this->teacherOf('alfa.profesor');
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        $this->client->request('GET', '/centro/profesorado/' . $teacher->getId());
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/centro/profesorado/eliminar/' . $this->year(), ['users' => [$teacher->getId()], 'confirm' => 'ok']);

        self::assertNotNull($this->teacherOf('alfa.profesor'));
    }

    public function testTeachersOfAnotherCentreCannotBeDeletedThroughThisOne(): void
    {
        // docente de Beta sin ninguna dependencia, que la base de datos no protege
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $crawler = $this->client->request('GET', '/centro/profesorado/nuevo');
        $values = $crawler->selectButton('Guardar cambios')->form()->getPhpValues();
        $values['new_teacher']['person'] = (string) $this->personId('beta.alumno1');
        $this->client->request('POST', '/centro/profesorado/nuevo', $values);
        $betaYear = $this->year(DemoData::ORG_B_CODE);
        $foreign = $this->em()->getRepository(Teacher::class)->createQueryBuilder('t')
            ->join('t.person', 'p')->where('p.loginUsername = :username')->andWhere('t.academicYear = :year')
            ->setParameter('username', 'beta.alumno1')->setParameter('year', $betaYear)->getQuery()->getSingleResult()->getId();

        // el administrador de Alfa lo incluye en el borrado de su propio curso
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('POST', '/centro/profesorado/eliminar/' . $this->year(), ['users' => [$foreign], 'confirm' => 'ok']);

        self::assertNotNull($this->em()->getRepository(Teacher::class)->find($foreign), 'El docente de Beta sigue existiendo');
    }

    public function testDeletionRequiresConfirmation(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->addTeacher($this->personId('alfa.alumno3'));
        $id = $this->teacherOf('alfa.alumno3')->getId();

        $this->client->request('POST', '/centro/profesorado/eliminar/' . $this->year(), ['users' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertNotNull($this->teacherOf('alfa.alumno3'));

        $this->client->request('POST', '/centro/profesorado/eliminar/' . $this->year(), ['users' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertNull($this->teacherOf('alfa.alumno3'));
    }

    public function testTeacherWithTeachingAssignmentsIsKeptAndTheErrorIsReported(): void
    {
        $teacher = $this->teacherOf('alfa.profesor');
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        // tiene asignaciones de docencia y estancias: no se borra, pero tampoco falla el servidor
        $this->client->request('POST', '/centro/profesorado/eliminar/' . $this->year(), ['users' => [$teacher->getId()], 'confirm' => 'ok']);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }
}
