<?php

namespace App\Tests\Unit\Security;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Department;
use App\Entity\Edu\Teacher;
use App\Entity\Edu\Training;
use App\Entity\Organization;
use App\Entity\Person;
use App\Repository\Edu\TeacherRepository;
use App\Security\Edu\TrainingVoter;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;

class TrainingVoterTest extends VoterTestCase
{
    private Organization $organization;

    protected function setUp(): void
    {
        $this->organization = $this->organization();
    }

    /**
     * @param list<string> $securityGranted permisos que concede Security::isGranted()
     */
    private function voter(
        bool $globalAdmin = false,
        array $securityGranted = [],
        ?Teacher $teacher = null,
        ?Organization $currentOrganization = null
    ): TrainingVoter {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static fn ($attribute): bool => in_array($attribute, $securityGranted, true)
        );
        $teachers = $this->createStub(TeacherRepository::class);
        $teachers->method('findOneByPersonAndAcademicYear')->willReturn($teacher);

        return new TrainingVoter(
            $this->cache(),
            $this->userExtension($currentOrganization ?? $this->organization, $globalAdmin),
            $teachers,
            $security
        );
    }

    private function training(?Person $head = null, ?Organization $organization = null): Training
    {
        $academicYear = new AcademicYear();
        $academicYear->setOrganization($organization ?? $this->organization);
        $training = new Training();
        $training->setAcademicYear($academicYear);

        if ($head instanceof Person) {
            $teacher = new Teacher();
            $teacher->setPerson($head);
            $department = new Department();
            $department->setHead($teacher);
            $training->setDepartment($department);
        }
        return $training;
    }

    public function testTrainingFromAnotherOrganizationIsDeniedEvenToGlobalAdministrators(): void
    {
        $voter = $this->voter(true);
        $training = $this->training(null, new Organization());

        self::assertSame(self::denied(), $voter->vote($this->token($this->person(true)), $training, [TrainingVoter::MANAGE]));
        self::assertSame(self::denied(), $voter->vote($this->token($this->person(true)), $training, [TrainingVoter::ACCESS]));
    }

    public function testGlobalAdministratorIsGrantedInTheCurrentOrganization(): void
    {
        $voter = $this->voter(true);

        self::assertSame(
            self::granted(),
            $voter->vote($this->token($this->person(true)), $this->training(), [TrainingVoter::MANAGE])
        );
    }

    public function testAnonymousIsDenied(): void
    {
        self::assertSame(
            self::denied(),
            $this->voter()->vote($this->token(null), $this->training(), [TrainingVoter::MANAGE])
        );
    }

    /**
     * @dataProvider managerProvider
     */
    public function testOrganizationAndDualManagersAreGranted(string $permission): void
    {
        $voter = $this->voter(false, [$permission]);

        foreach ([TrainingVoter::MANAGE, TrainingVoter::ACCESS] as $attribute) {
            self::assertSame(
                self::granted(),
                $voter->vote($this->token($this->person()), $this->training(), [$attribute]),
                $attribute
            );
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador de la organización' => [OrganizationVoter::MANAGE];
        yield 'coordinador de FP dual' => [WltOrganizationVoter::WLT_MANAGER];
    }

    public function testDepartmentHeadIsGranted(): void
    {
        $user = $this->person();
        $voter = $this->voter();

        self::assertSame(self::granted(), $voter->vote($this->token($user), $this->training($user), [TrainingVoter::MANAGE]));
        // otro usuario distinto al jefe de departamento no obtiene el permiso
        self::assertSame(
            self::denied(),
            $voter->vote($this->token($this->person()), $this->training($user), [TrainingVoter::MANAGE])
        );
    }

    public function testTeacherCanAccessButNotManage(): void
    {
        $voter = $this->voter(false, [], new Teacher());
        $token = $this->token($this->person());
        $training = $this->training();

        self::assertSame(self::granted(), $voter->vote($token, $training, [TrainingVoter::ACCESS]));
        self::assertSame(self::denied(), $voter->vote($token, $training, [TrainingVoter::MANAGE]));
    }

    public function testNonTeacherCannotAccess(): void
    {
        self::assertSame(
            self::denied(),
            $this->voter()->vote($this->token($this->person()), $this->training(), [TrainingVoter::ACCESS])
        );
    }

    public function testDepartmentWithoutHeadDoesNotBreakTheVote(): void
    {
        $training = $this->training();
        $training->setDepartment(new Department());

        self::assertSame(
            self::denied(),
            $this->voter()->vote($this->token($this->person()), $training, [TrainingVoter::MANAGE])
        );
    }
}
