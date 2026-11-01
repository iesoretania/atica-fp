<?php

declare(strict_types=1);

namespace App\Service\WltModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WltModule\GroupRepository as WltGroupRepository;
use App\Repository\WltModule\TeacherRepository as WltTeacherRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Docentes que la persona conectada puede elegir como autores de una reunión de FP dual. Lo usan el controlador de
 * reuniones y el componente del formulario, que debe calcular lo mismo en cada actualización.
 */
final class MeetingTeacherProvider
{
    public function __construct(
        private readonly Security $security,
        private readonly UserExtensionService $userExtensionService,
        private readonly TeacherRepository $teacherRepository,
        private readonly WltTeacherRepository $wltTeacherRepository,
        private readonly WltGroupRepository $wltGroupRepository,
    ) {
    }

    /**
     * @return array<int, Teacher>
     */
    public function forAcademicYear(AcademicYear $academicYear, bool $readOnly): array
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $this->security->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $teacher = null;

        if (!$isManager) {
            $person = $this->security->getUser();
            if ($person instanceof Person) {
                if (!$isWltManager) {
                    // no es administrador ni coordinador de FP: puede ser jefe de departamento, tutor de grupo o
                    // profesor -> ver solo sus grupos
                    $teacher = $this->teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);
                    if ($teacher) {
                        $groups = $this->wltGroupRepository->findByAcademicYearAndPerson($academicYear, $person)->toArray();
                    }
                } else {
                    $groups = $this->wltGroupRepository->findByAcademicYearAndPerson($academicYear, $person)->toArray();
                }
            }
        }

        $teachers = [];
        if (!$isManager && !$isDepartmentHead && $teacher && !$readOnly) {
            $teachers = [$teacher];
        } elseif ($groups) {
            $teachers = $this->teacherRepository->findByGroups($groups);
        }

        if (!$teachers) {
            $teachers = $this->wltTeacherRepository->findByAcademicYear($academicYear);
        }

        return $teachers;
    }
}
