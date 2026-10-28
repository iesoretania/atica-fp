<?php

namespace App\Tests\Functional;

use App\Entity\Organization;
use App\Entity\Person;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Comandos de consola para crear administradores y organizaciones. Cada test se revierte al terminar.
 */
class ConsoleCommandsTest extends KernelTestCase
{
    private function tester(string $name): CommandTester
    {
        self::bootKernel();

        return new CommandTester((new Application(self::$kernel))->find($name));
    }

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function person(string $username): ?Person
    {
        return $this->em()->getRepository(Person::class)->findOneBy(['loginUsername' => $username]);
    }

    // --- app:admin -----------------------------------------------------------------------------

    public function testAdminCommandCreatesAGlobalAdministrator(): void
    {
        $tester = $this->tester('app:admin');

        $status = $tester->execute(['username' => 'nuevo.admin', '--password' => 'ClaveSegura-2026']);

        self::assertSame(0, $status);
        $person = $this->person('nuevo.admin');
        self::assertInstanceOf(Person::class, $person);
        self::assertTrue($person->isGlobalAdministrator());
        self::assertTrue($person->isEnabled());
        // debe cambiar la contraseña en el primer acceso, y se guarda cifrada
        self::assertTrue($person->isForcePasswordChange());
        self::assertNotSame('ClaveSegura-2026', $person->getPassword());
        self::assertTrue(static::getContainer()->get('security.user_password_hasher')->isPasswordValid($person, 'ClaveSegura-2026'));
    }

    public function testAdminCommandUsesTheGivenNames(): void
    {
        $this->tester('app:admin')->execute([
            'username' => 'otro.admin', '--password' => 'ClaveSegura-2026', '--firstname' => 'Ana', '--lastname' => 'Pérez',
        ]);

        $person = $this->person('otro.admin');
        self::assertSame('Ana', $person->getFirstName());
        self::assertSame('Pérez', $person->getLastName());
    }

    public function testAdminCommandUpdatesAnExistingUserWithoutDuplicatingIt(): void
    {
        $count = $this->em()->getRepository(Person::class)->count([]);

        $status = $this->tester('app:admin')->execute(['username' => DemoData::USER_A_TEACHER, '--password' => 'ClaveSegura-2026']);

        self::assertSame(0, $status);
        self::assertSame($count, $this->em()->getRepository(Person::class)->count([]));
        self::assertTrue($this->person(DemoData::USER_A_TEACHER)->isGlobalAdministrator());
    }

    public function testAdminCommandAsksForThePasswordWhenNotGiven(): void
    {
        $tester = $this->tester('app:admin');
        $tester->setInputs(['ClaveInteractiva-2026']);

        $status = $tester->execute(['username' => 'interactivo.admin']);

        self::assertSame(0, $status);
        $person = $this->person('interactivo.admin');
        self::assertTrue(static::getContainer()->get('security.user_password_hasher')->isPasswordValid($person, 'ClaveInteractiva-2026'));
    }

    // --- app:organization ----------------------------------------------------------------------

    public function testOrganizationCommandCreatesAnOrganization(): void
    {
        $status = $this->tester('app:organization')->execute([
            'name' => 'I.E.S. Gamma', '--code' => '23000003', '--city' => 'Jaén',
        ]);

        self::assertSame(0, $status);
        $organization = $this->em()->getRepository(Organization::class)->findOneBy(['code' => '23000003']);
        self::assertInstanceOf(Organization::class, $organization);
        self::assertSame('I.E.S. Gamma', $organization->getName());
        self::assertSame('Jaén', $organization->getCity());
    }

    public function testOrganizationCommandRefusesToCreateADuplicate(): void
    {
        $count = $this->em()->getRepository(Organization::class)->count([]);

        $status = $this->tester('app:organization')->execute([
            'name' => DemoData::ORG_A_NAME, '--code' => '23999999', '--city' => 'Jaén',
        ]);

        self::assertSame(1, $status);
        self::assertSame($count, $this->em()->getRepository(Organization::class)->count([]));
    }
}
