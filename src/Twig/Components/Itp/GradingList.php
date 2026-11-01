<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\ItpModule\ProgramGroup;
use App\Entity\Person;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Estancias para valorar (por curso académico) o del alumnado de un grupo (evaluación), con enlace a la valoración
 * y a su informe. Con programGroup se listan las del grupo; si no, las del curso académico.
 */
#[AsLiveComponent('Itp:GradingList', template: 'components/Itp/GradingList.html.twig')]
final class GradingList extends AbstractListComponent
{
    #[LiveProp]
    public ?ProgramGroup $programGroup = null;

    /** Rutas de la valoración y de su informe (se firman con el resto de propiedades). */
    #[LiveProp]
    public string $urlPath = '';

    #[LiveProp]
    public string $reportPath = '';

    private StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository;

    #[Required]
    public function setStudentProgramWorkcenterRepository(StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository): void
    {
        $this->studentProgramWorkcenterRepository = $studentProgramWorkcenterRepository;
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        if ($this->programGroup instanceof ProgramGroup) {
            $this->denyUnlessGranted(ItpOrganizationVoter::ITP_VIEW_EVALUATION, $organization);
            if ($this->programGroup->getGroup()->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $organization) {
                throw new AccessDeniedException();
            }

            return;
        }
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_VIEW_GRADES, $organization);
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function sortableColumns(): array
    {
        return [
            'student' => ['p.lastName', 'p.firstName'],
            'workcenter' => ['c.name', 'w.name'],
            'start' => 'spw.startDate',
        ];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $this->organization());
        $q = $this->q !== '' ? $this->q : null;

        if ($this->programGroup instanceof ProgramGroup) {
            return $this->studentProgramWorkcenterRepository->createGradingProgramGroupQueryBuilder($this->programGroup, $person, $isManager, $q, true);
        }

        return $this->studentProgramWorkcenterRepository->createGradingQueryBuilder($this->academicYear, $person, $isManager, $q);
    }
}
