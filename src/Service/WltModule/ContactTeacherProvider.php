<?php

declare(strict_types=1);

namespace App\Service\WltModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WltModule\Contact;
use App\Repository\WltModule\GroupRepository as WltGroupRepository;
use App\Repository\WltModule\TeacherRepository as WltTeacherRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Docentes que la persona conectada puede elegir al registrar un contacto de FP dual. Lo usan el controlador de
 * contactos y el componente del formulario, que debe calcular lo mismo en cada actualización.
 */
final class ContactTeacherProvider
{
    public function __construct(
        private readonly Security $security,
        private readonly UserExtensionService $userExtensionService,
        private readonly WltTeacherRepository $wltTeacherRepository,
        private readonly WltGroupRepository $wltGroupRepository,
    ) {
    }

    /** Curso académico del contacto: el de su docente o, si no tiene, el actual del centro. */
    public function academicYear(Contact $contact): AcademicYear
    {
        $teacher = $contact->getTeacher();

        return $teacher instanceof Teacher
            ? $teacher->getAcademicYear()
            : $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
    }

    /**
     * @return array<int, Teacher>
     */
    public function forContact(Contact $contact, bool $readOnly): array
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        $academicYear = $this->academicYear($contact);
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $this->security->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $teacher = null;
        $person = $this->security->getUser();

        if (!$isManager) {
            if ($person instanceof Person) {
                if (!$isWltManager) {
                    // no es administrador ni coordinador de FP: puede ser jefe de departamento, tutor de grupo o
                    // profesor -> ver solo sus grupos
                    $teacher = $this->wltTeacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);
                    if ($teacher) {
                        $groups = $this->wltGroupRepository->findByAcademicYearAndWltTeacherPerson($academicYear, $person);
                    }
                } else {
                    $groups = $this->wltGroupRepository->findByAcademicYearAndWltTeacherPerson($academicYear, $person);
                }
            }
        } else {
            $groups = $this->wltGroupRepository->findByAcademicYear($academicYear);
        }

        $teachers = [];
        if (!$isManager && !$isDepartmentHead && $teacher && !$readOnly) {
            $teachers = [$teacher];
        } elseif ($groups) {
            $teachers = $this->wltTeacherRepository->findByGroupsOrEducationalTutor($groups, $academicYear);
        }
        // quien puede modificar un contacto ajeno (la jefatura, por ejemplo) debe ver al docente que lo registró
        if ($contact->getTeacher() instanceof Teacher && !in_array($contact->getTeacher(), $teachers, true)) {
            $teachers[] = $contact->getTeacher();
        }

        return $teachers;
    }
}
