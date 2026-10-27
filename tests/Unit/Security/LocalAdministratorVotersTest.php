<?php

namespace App\Tests\Unit\Security;

use App\Entity\Edu\PerformanceScale;
use App\Entity\Role;
use App\Entity\Survey;
use App\Repository\RoleRepository;
use App\Security\Edu\PerformanceScaleVoter;
use App\Security\SurveyVoter;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voters cuyo permiso de gestión depende solo del rol de administrador local de la organización
 */
class LocalAdministratorVotersTest extends VoterTestCase
{
    /**
     * @return iterable<string, array{class-string<Voter>, class-string, string}>
     */
    public static function voterProvider(): iterable
    {
        yield 'escala de valoración' => [PerformanceScaleVoter::class, PerformanceScale::class, PerformanceScaleVoter::MANAGE];
        yield 'encuesta' => [SurveyVoter::class, Survey::class, SurveyVoter::MANAGE];
    }

    private function create(string $voterClass, bool $hasRole, ?array &$calls = null, bool $globalAdmin = false): Voter
    {
        $roles = $this->createStub(RoleRepository::class);
        $roles->method('personHasRole')->willReturnCallback(
            static function ($organization, $person, $role) use ($hasRole, &$calls): bool {
                $calls[] = [$organization, $person, $role];
                return $hasRole;
            }
        );

        return new $voterClass($this->cache(), $this->decisionManager($globalAdmin ? ['ROLE_ADMIN'] : []), $roles);
    }

    /**
     * @dataProvider voterProvider
     */
    public function testLocalAdministratorOfTheSubjectOrganizationIsGranted(string $voterClass, string $subjectClass, string $attribute): void
    {
        $organization = $this->organization();
        $subject = new $subjectClass();
        $subject->setOrganization($organization);
        $user = $this->person();
        $calls = [];

        $vote = $this->create($voterClass, true, $calls)->vote($this->token($user), $subject, [$attribute]);

        self::assertSame(self::granted(), $vote);
        // se comprueba el rol en la organización del objeto, no en otra cualquiera
        self::assertSame([[$organization, $user, Role::ROLE_LOCAL_ADMIN]], $calls);
    }

    /**
     * @dataProvider voterProvider
     */
    public function testUserWithoutTheRoleIsDenied(string $voterClass, string $subjectClass, string $attribute): void
    {
        $subject = new $subjectClass();
        $subject->setOrganization($this->organization());

        self::assertSame(
            self::denied(),
            $this->create($voterClass, false)->vote($this->token($this->person()), $subject, [$attribute])
        );
    }

    /**
     * @dataProvider voterProvider
     */
    public function testGlobalAdministratorSkipsTheRoleCheck(string $voterClass, string $subjectClass, string $attribute): void
    {
        $subject = new $subjectClass();
        $calls = [];

        $vote = $this->create($voterClass, false, $calls, true)->vote($this->token($this->person(true)), $subject, [$attribute]);

        self::assertSame(self::granted(), $vote);
        self::assertSame([], $calls);
    }

    /**
     * @dataProvider voterProvider
     */
    public function testAnonymousIsDenied(string $voterClass, string $subjectClass, string $attribute): void
    {
        $subject = new $subjectClass();
        $subject->setOrganization($this->organization());

        self::assertSame(
            self::denied(),
            $this->create($voterClass, true)->vote($this->token(null), $subject, [$attribute])
        );
    }
}
