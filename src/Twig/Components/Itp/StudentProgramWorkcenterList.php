<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\ItpModule\StudentProgram;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\ItpModule\TrainingProgramVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Estancias de un estudiante en un programa formativo de FFEOE, con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Itp:StudentProgramWorkcenterList', template: 'components/Itp/StudentProgramWorkcenterList.html.twig')]
final class StudentProgramWorkcenterList extends AbstractListComponent
{
    #[LiveProp]
    public StudentProgram $studentProgram;

    private StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository;

    #[Required]
    public function setStudentProgramWorkcenterRepository(StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository): void
    {
        $this->studentProgramWorkcenterRepository = $studentProgramWorkcenterRepository;
    }

    protected function authorize(): void
    {
        $programGrade = $this->studentProgram->getProgramGroup()->getProgramGrade();
        $this->denyUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());
        if ($programGrade->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $this->organization()) {
            throw new AccessDeniedException();
        }
    }

    protected function fetchJoinCollection(): bool
    {
        return true;
    }

    protected function sortableColumns(): array
    {
        return ['workcenter' => ['c.name', 'w.name'], 'start' => 'spw.startDate', 'end' => 'spw.endDate'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->studentProgramWorkcenterRepository->createByStudentProgramQueryBuilder($this->studentProgram, $this->q !== '' ? $this->q : null);
    }

    /** Actividades y horas por estancia. */
    public function getStats(): array
    {
        return $this->studentProgramWorkcenterRepository->getActivityAndHoursStatsByStudentProgramAndFilter($this->studentProgram, $this->q !== '' ? $this->q : null);
    }
}
