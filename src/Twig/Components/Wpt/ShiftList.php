<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\Edu\Grade;
use App\Entity\WptModule\Agreement;
use App\Entity\WptModule\Shift;
use App\Security\OrganizationVoter;
use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Listado de convocatorias de FCT de un curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:ShiftList', template: 'components/Wpt/ShiftList.html.twig')]
final class ShiftList extends AbstractListComponent
{
    protected function authorize(): void
    {
        $this->denyUnlessGranted(WptOrganizationVoter::WPT_MANAGER, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    public function filterDefinitions(): array
    {
        return [
            'locked' => [
                'type' => 'select',
                'label' => $this->translator->trans('header.locked', [], 'wpt_shift'),
                'choices' => ['1' => $this->translator->trans('filters.yes', [], 'general'), '0' => $this->translator->trans('filters.no', [], 'general')],
            ],
        ];
    }

    protected function sortableColumns(): array
    {
        return ['name' => 's.name', 'hours' => 's.hours'];
    }

    /** @var array<int, int> acuerdos de cada convocatoria de la página, por identificador */
    private array $agreementCounts = [];

    /** Los grupos de cada curso y el número de acuerdos de cada convocatoria se cargan de una vez. */
    protected function preload(array $items): void
    {
        $grades = array_values(array_filter(array_map(static fn (Shift $shift) => $shift->getGrade(), $items)));
        if ($grades !== []) {
            $this->newQueryBuilder()
                ->select('gr', 'g')
                ->from(Grade::class, 'gr')
                ->leftJoin('gr.groups', 'g')
                ->where('gr IN (:grades)')
                ->setParameter('grades', $grades)
                ->getQuery()
                ->getResult();
        }
        $rows = $this->newQueryBuilder()
            ->select('IDENTITY(a.shift) AS shift_id', 'COUNT(a.id) AS total')
            ->from(Agreement::class, 'a')
            ->where('a.shift IN (:shifts)')
            ->setParameter('shifts', $items)
            ->groupBy('a.shift')
            ->getQuery()
            ->getArrayResult();
        foreach ($rows as $row) {
            $this->agreementCounts[(int) $row['shift_id']] = (int) $row['total'];
        }
    }

    public function agreementCount(Shift $shift): int
    {
        return $this->agreementCounts[$shift->getId()] ?? 0;
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('s', 'su', 'gr')
            ->distinct()
            ->from(Shift::class, 's')
            ->join('s.subject', 'su')
            ->leftJoin('su.grade', 'gr')
            ->leftJoin('gr.groups', 'g')
            ->join('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'h')
            ->orderBy('s.name');

        if ($this->q !== '') {
            $queryBuilder
                ->where('s.name LIKE :tq')
                ->orWhere('gr.name LIKE :tq')
                ->orWhere('tr.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        if (!$this->security->isGranted(OrganizationVoter::MANAGE, $this->organization())) {
            $queryBuilder
                ->andWhere('d.head IS NOT NULL AND h.person = :manager')
                ->setParameter('manager', $this->security->getUser());
        }

        if (($locked = $this->filter('locked')) !== '') {
            $queryBuilder->andWhere('s.locked = :filter_locked')->setParameter('filter_locked', $locked === '1');
        }

        return $queryBuilder
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
