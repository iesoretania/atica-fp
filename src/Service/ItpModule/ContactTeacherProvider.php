<?php

declare(strict_types=1);

namespace App\Service\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\Contact;
use App\Entity\Person;
use App\Repository\ItpModule\TeacherRepository as ItpTeacherRepository;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Docentes que la persona conectada puede elegir al registrar un contacto de FFEOE. Lo usan el controlador de
 * contactos y el componente del formulario, que debe calcular lo mismo en cada actualización.
 */
final class ContactTeacherProvider
{
    public function __construct(
        private readonly Security $security,
        private readonly UserExtensionService $userExtensionService,
        private readonly ItpTeacherRepository $itpTeacherRepository,
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
     * @return list<Teacher>
     */
    public function forContact(Contact $contact, bool $readOnly): array
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        $academicYear = $this->academicYear($contact);
        $person = $this->security->getUser();

        if (!$this->security->isGranted(OrganizationVoter::MANAGE, $organization)) {
            $teacher = $person instanceof Person
                ? $this->itpTeacherRepository->findOneByAcademicYearAndPerson($academicYear, $person)
                : null;
            $teachers = $teacher instanceof Teacher ? [$teacher] : [];
        } else {
            $teachers = $this->itpTeacherRepository->findByAcademicYear($academicYear);
        }

        if ($readOnly && $contact->getTeacher() instanceof Teacher) {
            return [$contact->getTeacher()];
        }
        if ($contact->getTeacher() instanceof Teacher && !in_array($contact->getTeacher(), $teachers, true)) {
            // quien puede modificar un contacto ajeno (la jefatura, por ejemplo) debe ver al docente que lo registró
            $teachers[] = $contact->getTeacher();
        }

        return $teachers;
    }
}
