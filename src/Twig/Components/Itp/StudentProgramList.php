<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\ItpModule\ProgramGroup;
use App\Repository\ItpModule\ProgramGradeRepository;
use App\Repository\ItpModule\ProgramGroupRepository;
use App\Repository\ItpModule\StudentProgramRepository;
use App\Security\ItpModule\TrainingProgramVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Estudiantes (programas individuales) de un grupo de un programa formativo de FFEOE. */
#[AsLiveComponent('Itp:StudentProgramList', template: 'components/Itp/StudentProgramList.html.twig')]
final class StudentProgramList extends AbstractListComponent
{
    #[LiveProp]
    public ProgramGroup $programGroup;

    private StudentProgramRepository $studentProgramRepository;
    private ProgramGroupRepository $programGroupRepository;
    private ProgramGradeRepository $programGradeRepository;

    #[Required]
    public function setRepositories(
        StudentProgramRepository $studentProgramRepository,
        ProgramGroupRepository $programGroupRepository,
        ProgramGradeRepository $programGradeRepository,
    ): void {
        $this->studentProgramRepository = $studentProgramRepository;
        $this->programGroupRepository = $programGroupRepository;
        $this->programGradeRepository = $programGradeRepository;
    }

    protected function authorize(): void
    {
        $programGrade = $this->programGroup->getProgramGrade();
        $this->denyUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());
        if ($programGrade->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $this->organization()) {
            throw new AccessDeniedException();
        }
    }

    public function filterDefinitions(): array
    {
        return [
            'workcenter' => [
                'type' => 'select',
                'label' => $this->translator->trans('filters.workcenter', [], 'itp_student_program'),
                'choices' => [
                    'with' => $this->translator->trans('filters.workcenter.with', [], 'itp_student_program'),
                    'without' => $this->translator->trans('filters.workcenter.without', [], 'itp_student_program'),
                ],
            ],
        ];
    }

    protected function sortableColumns(): array
    {
        return ['student' => ['s.lastName', 's.firstName']];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->studentProgramRepository
            ->createByProgramGroupQueryBuilder($this->programGroup, $this->q !== '' ? $this->q : null)
            ->orderBy('s.lastName')
            ->addOrderBy('s.firstName');

        switch ($this->filter('workcenter')) {
            case 'with':
                $queryBuilder->andWhere('sp.studentProgramWorkcenters IS NOT EMPTY');
                break;
            case 'without':
                $queryBuilder->andWhere('sp.studentProgramWorkcenters IS EMPTY');
                break;
        }

        return $queryBuilder;
    }

    /** Horas de jornadas por estudiante (matrícula). */
    public function getWorkDaysStats(): array
    {
        return $this->programGroupRepository->getStudentEnrollmentWorkDaysStats($this->programGroup);
    }

    /** Actividades realizadas por estudiante (matrícula). */
    public function getActivitiesStats(): array
    {
        return $this->programGroupRepository->getStudentEnrollmentActivitiesStats($this->programGroup);
    }

    public function getTotalActivities(): int
    {
        return (int) $this->programGradeRepository->countActivities($this->programGroup->getProgramGrade());
    }
}
