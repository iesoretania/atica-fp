<?php

namespace App\Tests\Fixtures;

use App\Entity\Person;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Utilidades comunes a las fixtures de demostración
 */
abstract class AbstractDemoFixture extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    /**
     * Impide cargar datos sobre una base de datos que no sea de tests o que ya tenga datos
     */
    protected function assertSafeDatabase(ObjectManager $manager): void
    {
        $connection = $manager->getConnection();
        $database = (string) $connection->getDatabase();
        if (!str_contains($database, '_test')) {
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

    protected function createPerson(
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
}
