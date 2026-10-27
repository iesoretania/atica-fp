<?php

namespace App\Tests\Unit\Security;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Entity\Person;
use App\Service\UserExtensionService;
use App\Repository\OrganizationRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Utilidades comunes para los tests de los Voters
 */
abstract class VoterTestCase extends TestCase
{
    protected function organization(): Organization
    {
        $organization = new Organization();
        $organization->setCurrentAcademicYear(new AcademicYear());
        return $organization;
    }

    protected function person(bool $globalAdministrator = false): Person
    {
        $person = new Person();
        $person->setGlobalAdministrator($globalAdministrator);
        return $person;
    }

    protected function token(?UserInterface $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        return $token;
    }

    protected function cache(): ArrayAdapter
    {
        return new ArrayAdapter();
    }

    /**
     * Gestor de decisiones que concede únicamente los atributos indicados.
     *
     * @param list<string> $granted atributos concedidos (por ejemplo, 'ROLE_ADMIN')
     */
    protected function decisionManager(array $granted = []): AccessDecisionManagerInterface
    {
        $manager = $this->createStub(AccessDecisionManagerInterface::class);
        $manager->method('decide')->willReturnCallback(
            static fn ($token, array $attributes, $object = null): bool => array_intersect($attributes, $granted) !== []
        );
        return $manager;
    }

    /**
     * UserExtensionService real (sus métodos son final) con la organización indicada en la sesión.
     */
    protected function userExtension(Organization $organization, bool $globalAdmin = false): UserExtensionService
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('organization_id', 1);
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(
            static fn ($attribute): bool => $globalAdmin && $attribute === 'ROLE_ADMIN'
        );

        $repository = $this->createStub(OrganizationRepository::class);
        $repository->method('find')->willReturn($organization);

        return new UserExtensionService($requestStack, $checker, $repository);
    }

    protected static function granted(): int
    {
        return VoterInterface::ACCESS_GRANTED;
    }

    protected static function denied(): int
    {
        return VoterInterface::ACCESS_DENIED;
    }
}
