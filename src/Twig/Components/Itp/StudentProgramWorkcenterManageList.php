<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\ItpModule\ProgramGrade;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\ItpModule\TrainingProgramVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Gestión de las estancias de todo el alumnado de un ciclo de un programa formativo de FFEOE. */
#[AsLiveComponent('Itp:StudentProgramWorkcenterManageList', template: 'components/Itp/StudentProgramWorkcenterManageList.html.twig')]
final class StudentProgramWorkcenterManageList extends AbstractListComponent
{
    #[LiveProp]
    public ProgramGrade $programGrade;

    private StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository;

    #[Required]
    public function setStudentProgramWorkcenterRepository(StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository): void
    {
        $this->studentProgramWorkcenterRepository = $studentProgramWorkcenterRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(TrainingProgramVoter::MANAGE, $this->programGrade->getTrainingProgram());
        if ($this->programGrade->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $this->organization()) {
            throw new AccessDeniedException();
        }
    }

    public function filterDefinitions(): array
    {
        $groups = [];
        foreach ($this->programGrade->getGrade()->getGroups() as $group) {
            $groups[(string) $group->getId()] = $group->getName();
        }

        return [
            'group' => [
                'type' => 'select',
                'label' => $this->translator->trans('filters.group', [], 'itp_student_program_workcenter'),
                'choices' => $groups,
            ],
        ];
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
        $queryBuilder = $this->studentProgramWorkcenterRepository->createFindByProgramGradeAndFilterQueryBuilder($this->programGrade, $this->q !== '' ? $this->q : null);
        if (($group = $this->filter('group')) !== '') {
            $queryBuilder->andWhere('g.id = :group_id')->setParameter('group_id', (int) $group);
        }

        return $queryBuilder;
    }

    public function getStats(): array
    {
        return $this->studentProgramWorkcenterRepository->getStatsByProgramGradeAndFilter($this->programGrade, $this->q !== '' ? $this->q : null);
    }
}
