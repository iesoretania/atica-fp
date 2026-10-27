<?php

namespace App\Tests\Unit\Listener;

use App\Entity\Company;
use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Department;
use App\Entity\Organization;
use App\Listener\OrganizationOwnershipSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class OrganizationOwnershipSubscriberTest extends TestCase
{
    private function organization(int $id): Organization
    {
        $organization = new Organization();
        (new \ReflectionProperty(Organization::class, 'id'))->setValue($organization, $id);

        return $organization;
    }

    private function academicYear(Organization $organization): AcademicYear
    {
        $academicYear = new AcademicYear();
        $academicYear->setOrganization($organization);

        return $academicYear;
    }

    /**
     * @param list<mixed> $arguments
     */
    private function dispatch(array $arguments, ?int $sessionOrganizationId, bool $globalAdmin = false, int $type = HttpKernelInterface::MAIN_REQUEST): void
    {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        if ($sessionOrganizationId !== null) {
            $session->set('organization_id', $sessionOrganizationId);
        }
        $request->setSession($session);

        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(static fn ($attribute): bool => $globalAdmin && $attribute === 'ROLE_ADMIN');

        $event = new ControllerArgumentsEvent(
            $this->createStub(HttpKernelInterface::class),
            static fn () => null,
            $arguments,
            $request,
            $type
        );
        (new OrganizationOwnershipSubscriber($checker))->onKernelControllerArguments($event);
    }

    public function testEntityOfTheCurrentOrganizationIsAllowed(): void
    {
        $this->dispatch([$this->academicYear($this->organization(1))], 1);
        $this->addToAssertionCount(1);
    }

    public function testEntityOfAnotherOrganizationIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->dispatch([$this->academicYear($this->organization(2))], 1);
    }

    public function testOwnershipIsResolvedThroughParentEntities(): void
    {
        $department = new Department();
        $department->setAcademicYear($this->academicYear($this->organization(2)));

        $this->expectException(AccessDeniedException::class);
        $this->dispatch([$department], 1);
    }

    public function testGlobalAdministratorCanAccessAnyOrganization(): void
    {
        $this->dispatch([$this->academicYear($this->organization(2))], 1, true);
        $this->addToAssertionCount(1);
    }

    public function testEntitiesWithoutOrganizationAreIgnored(): void
    {
        // las empresas son comunes a todas las organizaciones
        $this->dispatch([new Company(), 'texto', 5, null], 1);
        $this->addToAssertionCount(1);
    }

    public function testNothingIsCheckedWithoutASelectedOrganization(): void
    {
        $this->dispatch([$this->academicYear($this->organization(2))], null);
        $this->addToAssertionCount(1);
    }

    public function testSubRequestsAreNotChecked(): void
    {
        $this->dispatch([$this->academicYear($this->organization(2))], 1, false, HttpKernelInterface::SUB_REQUEST);
        $this->addToAssertionCount(1);
    }
}
