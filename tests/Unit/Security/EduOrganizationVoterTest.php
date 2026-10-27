<?php

namespace App\Tests\Unit\Security;

use App\Entity\Edu\Teacher;
use App\Repository\Edu\TeacherRepository;
use App\Repository\Edu\TrainingRepository;
use App\Security\Edu\OrganizationVoter;
use App\Security\OrganizationVoter as BaseOrganizationVoter;

class EduOrganizationVoterTest extends VoterTestCase
{
    private function voter(array $granted = [], int $headedTrainings = 0, ?Teacher $teacher = null): OrganizationVoter
    {
        $trainings = $this->createStub(TrainingRepository::class);
        $trainings->method('countAcademicYearAndDepartmentHead')->willReturn($headedTrainings);
        $teachers = $this->createStub(TeacherRepository::class);
        $teachers->method('findOneByAcademicYearAndPerson')->willReturn($teacher);

        return new OrganizationVoter($this->cache(), $this->decisionManager($granted), $trainings, $teachers);
    }

    public function testTeacherNeedsToBeTeacherOfTheCurrentAcademicYear(): void
    {
        $token = $this->token($this->person());
        $organization = $this->organization();

        self::assertSame(
            self::granted(),
            $this->voter([], 0, new Teacher())->vote($token, $organization, [OrganizationVoter::EDU_TEACHER])
        );
        self::assertSame(
            self::denied(),
            $this->voter()->vote($token, $organization, [OrganizationVoter::EDU_TEACHER])
        );
    }

    public function testDepartmentHeadNeedsToHeadAtLeastOneTraining(): void
    {
        $token = $this->token($this->person());
        $organization = $this->organization();

        self::assertSame(
            self::granted(),
            $this->voter([], 1)->vote($token, $organization, [OrganizationVoter::EDU_DEPARTMENT_HEAD])
        );
        self::assertSame(
            self::denied(),
            $this->voter([], 0)->vote($token, $organization, [OrganizationVoter::EDU_DEPARTMENT_HEAD])
        );
    }

    public function testFinancialManagerMustBeTheTeacherDesignatedForTheAcademicYear(): void
    {
        $token = $this->token($this->person());
        $organization = $this->organization();
        $teacher = new Teacher();

        // es profesor, pero no es el responsable económico designado
        self::assertSame(
            self::denied(),
            $this->voter([], 0, $teacher)->vote($token, $organization, [OrganizationVoter::EDU_FINANCIAL_MANAGER])
        );

        $organization->getCurrentAcademicYear()->setFinancialManager($teacher);
        self::assertSame(
            self::granted(),
            $this->voter([], 0, $teacher)->vote($token, $organization, [OrganizationVoter::EDU_FINANCIAL_MANAGER])
        );

        // otro profesor distinto no obtiene el permiso
        self::assertSame(
            self::denied(),
            $this->voter([], 0, new Teacher())->vote($token, $organization, [OrganizationVoter::EDU_FINANCIAL_MANAGER])
        );
        // quien no es profesor tampoco
        self::assertSame(
            self::denied(),
            $this->voter()->vote($token, $organization, [OrganizationVoter::EDU_FINANCIAL_MANAGER])
        );
    }

    public function testLocalAdministratorHasEveryEduPermission(): void
    {
        $voter = $this->voter([BaseOrganizationVoter::LOCAL_MANAGE]);
        $token = $this->token($this->person());

        foreach ([OrganizationVoter::EDU_TEACHER, OrganizationVoter::EDU_DEPARTMENT_HEAD, OrganizationVoter::EDU_FINANCIAL_MANAGER] as $attribute) {
            self::assertSame(self::granted(), $voter->vote($token, $this->organization(), [$attribute]), $attribute);
        }
    }

    public function testGlobalAdministratorAndAnonymous(): void
    {
        self::assertSame(
            self::granted(),
            $this->voter(['ROLE_ADMIN'])->vote($this->token($this->person(true)), $this->organization(), [OrganizationVoter::EDU_TEACHER])
        );
        self::assertSame(
            self::denied(),
            $this->voter()->vote($this->token(null), $this->organization(), [OrganizationVoter::EDU_TEACHER])
        );
    }
}
