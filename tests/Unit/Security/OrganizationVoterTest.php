<?php

namespace App\Tests\Unit\Security;

use App\Entity\Role;
use App\Repository\OrganizationRepository;
use App\Repository\RoleRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;

class OrganizationVoterTest extends VoterTestCase
{
    private const ATTRIBUTES = [
        OrganizationVoter::MANAGE,
        OrganizationVoter::ACCESS,
        OrganizationVoter::ACCESS_SECTION,
        OrganizationVoter::LOCAL_MANAGE,
        OrganizationVoter::MANAGE_COMPANIES,
    ];

    /**
     * @param list<string> $granted
     */
    private function voter(array $granted = [], bool $localAdmin = false, bool $member = false): OrganizationVoter
    {
        $roles = $this->createMock(RoleRepository::class);
        $roles->method('personHasRole')->willReturnCallback(
            static fn ($organization, $person, $role): bool => $localAdmin && $role === Role::ROLE_LOCAL_ADMIN
        );
        $organizations = $this->createStub(OrganizationRepository::class);
        $organizations->method('findByUserAndOrganization')->willReturn($member);

        return new OrganizationVoter($this->cache(), $this->decisionManager($granted), $organizations, $roles);
    }

    public function testGlobalAdministratorHasEveryPermission(): void
    {
        $voter = $this->voter(['ROLE_ADMIN']);

        foreach (self::ATTRIBUTES as $attribute) {
            self::assertSame(
                self::granted(),
                $voter->vote($this->token($this->person(true)), $this->organization(), [$attribute]),
                $attribute
            );
        }
    }

    public function testAnonymousUserIsDeniedEverything(): void
    {
        $voter = $this->voter();

        foreach (self::ATTRIBUTES as $attribute) {
            self::assertSame(self::denied(), $voter->vote($this->token(null), $this->organization(), [$attribute]), $attribute);
        }
    }

    public function testLocalAdministratorCanManageTheOrganization(): void
    {
        $organization = $this->organization();
        $user = $this->person();

        // el rol de administrador local concede LOCAL_MANAGE...
        $voter = $this->voter([], true);
        self::assertSame(self::granted(), $voter->vote($this->token($user), $organization, [OrganizationVoter::LOCAL_MANAGE]));

        // ...y, al ser administrador local, también el resto de permisos
        $voter = $this->voter([OrganizationVoter::LOCAL_MANAGE], true);
        foreach (self::ATTRIBUTES as $attribute) {
            self::assertSame(self::granted(), $voter->vote($this->token($user), $organization, [$attribute]), $attribute);
        }
    }

    public function testRegularUserCannotManageTheOrganization(): void
    {
        $voter = $this->voter();
        $token = $this->token($this->person());
        $organization = $this->organization();

        self::assertSame(self::denied(), $voter->vote($token, $organization, [OrganizationVoter::LOCAL_MANAGE]));
        self::assertSame(self::denied(), $voter->vote($token, $organization, [OrganizationVoter::MANAGE]));
        self::assertSame(self::denied(), $voter->vote($token, $organization, [OrganizationVoter::ACCESS_SECTION]));
        self::assertSame(self::denied(), $voter->vote($token, $organization, [OrganizationVoter::MANAGE_COMPANIES]));
    }

    public function testAccessRequiresMembership(): void
    {
        $token = $this->token($this->person());
        $organization = $this->organization();

        self::assertSame(
            self::granted(),
            $this->voter([], false, true)->vote($token, $organization, [OrganizationVoter::ACCESS])
        );
        self::assertSame(
            self::denied(),
            $this->voter([], false, false)->vote($token, $organization, [OrganizationVoter::ACCESS])
        );
    }

    /**
     * @dataProvider sectionProvider
     */
    public function testSectionPermissions(string $attribute, string $delegated, bool $expected): void
    {
        $voter = $this->voter([$delegated]);

        self::assertSame(
            $expected ? self::granted() : self::denied(),
            $voter->vote($this->token($this->person()), $this->organization(), [$attribute])
        );
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function sectionProvider(): iterable
    {
        // ACCESS_SECTION: administrador financiero
        yield 'financial manager can access section' => [OrganizationVoter::ACCESS_SECTION, EduOrganizationVoter::EDU_FINANCIAL_MANAGER, true];
        yield 'teacher cannot access section' => [OrganizationVoter::ACCESS_SECTION, EduOrganizationVoter::EDU_TEACHER, false];
        // MANAGE_COMPANIES: jefe de departamento o coordinadores de FP dual y de FFE
        yield 'department head can manage companies' => [OrganizationVoter::MANAGE_COMPANIES, EduOrganizationVoter::EDU_DEPARTMENT_HEAD, true];
        yield 'dual coordinator can manage companies' => [OrganizationVoter::MANAGE_COMPANIES, WltOrganizationVoter::WLT_MANAGER, true];
        yield 'itp coordinator can manage companies' => [OrganizationVoter::MANAGE_COMPANIES, ItpOrganizationVoter::ITP_MANAGER, true];
        yield 'teacher cannot manage companies' => [OrganizationVoter::MANAGE_COMPANIES, EduOrganizationVoter::EDU_TEACHER, false];
    }

    public function testSupportsOnlyOrganizationsAndOwnAttributes(): void
    {
        $voter = $this->voter();

        self::assertTrue($voter->supports(OrganizationVoter::MANAGE, $this->organization()));
        self::assertFalse($voter->supports(OrganizationVoter::MANAGE, new \stdClass()));
        self::assertFalse($voter->supports('OTRO_ATRIBUTO', $this->organization()));
    }
}
