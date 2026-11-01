<?php

declare(strict_types=1);

namespace App\Service\WptModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WptModule\GroupRepository as WptGroupRepository;
use App\Repository\WptModule\TeacherRepository as WptTeacherRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Docentes que la persona conectada puede elegir al registrar una visita de FCT. Lo usan el controlador de visitas
 * y el componente del formulario, que debe calcular lo mismo en cada actualización.
 */
final class VisitTeacherProvider
{
    public function __construct(
        private readonly Security $security,
        private readonly UserExtensionService $userExtensionService,
        private readonly TeacherRepository $teacherRepository,
        private readonly WptTeacherRepository $wptTeacherRepository,
        private readonly WptGroupRepository $wptGroupRepository,
    ) {
    }

    /**
     * @return list<Teacher>
     */
    public function forAcademicYear(AcademicYear $academicYear, bool $readOnly): array
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isDepartmentHead = $this->security->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $teacher = null;

        if (false === $isManager) {
            $person = $this->security->getUser();

            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor -> ver sólo sus grupos
            if ($person instanceof Person) {
                $teacher = $this->teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

                if ($teacher) {
                    $groups = $this->wptGroupRepository
                        ->findByAcademicYearAndWptGroupTutorOrDepartmentHeadPerson($academicYear, $person);
                }
            }
        }

        if (!$isManager && !$isDepartmentHead && $teacher && !$readOnly) {
            return [$teacher];
        }
        if ($groups) {
            return $this->wptTeacherRepository->findByGroups($groups);
        }

        return $this->wptTeacherRepository->findByAcademicYear($academicYear);
    }
}
