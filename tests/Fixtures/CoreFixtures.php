<?php

namespace App\Tests\Fixtures;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Entity\Role;
use Doctrine\Persistence\ObjectManager;

/**
 * Datos básicos: administrador global y, para cada organización, su curso académico actual
 * y su administrador local.
 *
 * Se carga SOLO sobre una base de datos de tests recién migrada:
 *   bin/console doctrine:fixtures:load --append --env=test
 */
class CoreFixtures extends AbstractDemoFixture
{
    public const REF_ORGANIZATION = 'organization.';
    public const REF_ACADEMIC_YEAR = 'academic-year.';

    public function load(ObjectManager $manager): void
    {
        $this->assertSafeDatabase($manager);

        $this->createPerson($manager, DemoData::USER_ADMIN, 'Administrador', 'Global', true);

        foreach (DemoData::ORGANIZATIONS as $prefix => $data) {
            $organization = new Organization();
            $organization->setCode($data['code'])->setName($data['name'])->setCity($data['city']);
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

            $localAdmin = $this->createPerson(
                $manager,
                DemoData::username($prefix, DemoData::ROLE_LOCAL_ADMIN),
                'Administrador',
                'Local ' . $data['name']
            );
            $role = new Role();
            $role->setPerson($localAdmin)->setOrganization($organization)->setRole(Role::ROLE_LOCAL_ADMIN);
            $manager->persist($role);
            $manager->flush();

            $this->addReference(self::REF_ORGANIZATION . $prefix, $organization);
            $this->addReference(self::REF_ACADEMIC_YEAR . $prefix, $academicYear);
        }
    }
}
