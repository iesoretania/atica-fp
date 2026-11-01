<?php

declare(strict_types=1);

namespace App\Service\WltModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Repository\WltModule\GroupRepository as WltGroupRepository;
use App\Repository\WltModule\TeacherRepository as WltTeacherRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Docentes cuyos contactos puede consultar en los informes la persona conectada. Lo usan el controlador de contactos
 * y los listados dinámicos de informes, que deben calcular lo mismo en cada actualización.
 */
final class ContactReportTeacherProvider
{
    public function __construct(
        private readonly Security $security,
        private readonly UserExtensionService $userExtensionService,
        private readonly WltTeacherRepository $wltTeacherRepository,
        private readonly WltGroupRepository $wltGroupRepository,
    ) {
    }

    /**
     * @return array<int, Teacher>
     */
    public function forAcademicYear(AcademicYear $academicYear): array
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        // un curso de otro centro nunca ofrece docentes
        if ($academicYear->getOrganization() !== $organization) {
            return [];
        }
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $this->security->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $currentUserTeacher = null;
        $seesWholeScope = $isManager;

        if (!$isManager) {
            $person = $this->security->getUser();
            if (!$person instanceof Person) {
                return [];
            }

            $currentUserTeacher = $this->wltTeacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

            // la jefatura de departamento ve todos los grupos de sus enseñanzas y la coordinación los de los
            // proyectos que gestiona; el resto del profesorado, solo su propio informe
            $headGroups = $isDepartmentHead
                ? $this->wltGroupRepository->findByAcademicYearAndWLTDepartmentHeadPerson($academicYear, $person)
                : [];
            $managedGroups = $isWltManager
                ? $this->wltGroupRepository->findByAcademicYearAndWLTProjectManagerPerson($academicYear, $person)
                : [];
            $teachingGroups = $currentUserTeacher
                ? $this->wltGroupRepository->findByAcademicYearAndWltTeacherPerson($academicYear, $person)
                : [];

            foreach ([...$headGroups, ...$managedGroups, ...$teachingGroups] as $group) {
                $groups[$group->getId()] = $group;
            }
            $groups = array_values($groups);
            $seesWholeScope = $headGroups !== [] || $managedGroups !== [] || ($isWltManager && !$currentUserTeacher);
        } else {
            $groups = $this->wltGroupRepository->findByAcademicYear($academicYear);
        }

        if (!$seesWholeScope && $currentUserTeacher) {
            return [$currentUserTeacher];
        }

        $teachers = $groups ? $this->wltTeacherRepository->findByGroupsOrEducationalTutor($groups, $academicYear) : [];
        if ($currentUserTeacher && !in_array($currentUserTeacher, $teachers, true)) {
            $teachers[] = $currentUserTeacher;
        }

        return $teachers;
    }
}
