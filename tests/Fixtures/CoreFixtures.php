<?php

namespace App\Tests\Fixtures;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Department;
use App\Entity\Edu\Teacher;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Datos básicos: administrador global, dos organizaciones y, en cada una, un administrador
 * local y un profesor.
 *
 * Se carga SOLO sobre una base de datos de tests recién migrada:
 *   bin/console doctrine:fixtures:load --append --env=test
 */
class CoreFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->assertSafeDatabase($manager);

        $this->person($manager, DemoData::USER_ADMIN, 'Administrador', 'Global', true);

        $this->organization(
            $manager,
            DemoData::ORG_A_CODE,
            DemoData::ORG_A_NAME,
            'Linares',
            DemoData::USER_A_LOCAL_ADMIN,
            DemoData::USER_A_TEACHER,
            'alfa'
        );
        $this->organization(
            $manager,
            DemoData::ORG_B_CODE,
            DemoData::ORG_B_NAME,
            'Úbeda',
            DemoData::USER_B_LOCAL_ADMIN,
            DemoData::USER_B_TEACHER,
            'beta'
        );

        $manager->flush();
    }

    /**
     * Impide cargar datos sobre una base de datos que no sea de tests o que ya tenga datos
     */
    private function assertSafeDatabase(ObjectManager $manager): void
    {
        $connection = $manager->getConnection();
        $database = (string) $connection->getDatabase();
        if (!str_ends_with($database, '_test') && !str_contains($database, '_test')) {
            throw new \RuntimeException(sprintf(
                'Las fixtures solo pueden cargarse en una base de datos de tests (*_test); la actual es "%s".',
                $database
            ));
        }
        if ((int) $connection->fetchOne('SELECT COUNT(*) FROM person') > 0) {
            throw new \RuntimeException(
                'La base de datos ya contiene datos. Recréala y migra de nuevo antes de cargar las fixtures.'
            );
        }
    }

    private function person(
        ObjectManager $manager,
        string $username,
        string $firstName,
        string $lastName,
        bool $globalAdministrator = false
    ): Person {
        $person = new Person();
        $person
            ->setLoginUsername($username)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmailAddress($username . '@example.com')
            ->setEnabled(true)
            ->setGlobalAdministrator($globalAdministrator)
            ->setForcePasswordChange(false)
            ->setExternalCheck(false)
            ->setAllowExternalCheck(false);
        $person->setPassword($this->hasher->hashPassword($person, DemoData::PASSWORD));
        $manager->persist($person);

        return $person;
    }

    private function organization(
        ObjectManager $manager,
        string $code,
        string $name,
        string $city,
        string $localAdminUsername,
        string $teacherUsername,
        string $prefix
    ): void {
        $organization = new Organization();
        $organization->setCode($code)->setName($name)->setCity($city);
        $manager->persist($organization);
        // la organización y su curso actual se referencian entre sí: guardar primero la organización
        $manager->flush();

        $start = DemoData::currentSchoolYearStart();
        $academicYear = new AcademicYear();
        $academicYear
            ->setOrganization($organization)
            ->setDescription($start->format('Y') . '-' . ((int) $start->format('Y') + 1))
            ->setStartDate(\DateTime::createFromImmutable($start))
            ->setEndDate(\DateTime::createFromImmutable($start->modify('+1 year -1 day')));
        $manager->persist($academicYear);
        $organization->setCurrentAcademicYear($academicYear);
        $manager->flush();

        $localAdmin = $this->person($manager, $localAdminUsername, 'Administrador', 'Local ' . $name);
        $role = new Role();
        $role->setPerson($localAdmin)->setOrganization($organization)->setRole(Role::ROLE_LOCAL_ADMIN);
        $manager->persist($role);

        $teacherPerson = $this->person($manager, $teacherUsername, 'Profesor', 'Docente ' . $name);
        $department = new Department();
        $department->setName('Informática')->setAcademicYear($academicYear);
        $manager->persist($department);
        $teacher = new Teacher();
        $teacher->setPerson($teacherPerson)->setAcademicYear($academicYear)->setDepartment($department);
        $manager->persist($teacher);
        $manager->flush();
    }
}
