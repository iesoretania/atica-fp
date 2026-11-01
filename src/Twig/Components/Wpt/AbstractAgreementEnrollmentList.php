<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Controller\WptModule\TrackingController;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WptModule\GroupRepository as WptGroupRepository;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;

/**
 * Listados de acuerdos del alumnado de FCT visibles para la persona conectada, por curso académico (seguimiento e
 * informe final). Comparten consulta, filtros (convocatoria y grupo) y columnas ordenables; cada uno comprueba su
 * propio permiso en authorize().
 *
 * Las opciones de los filtros salen de la propia consulta (solo se ofrecen convocatorias y grupos que la persona ya
 * puede ver en el listado).
 */
abstract class AbstractAgreementEnrollmentList extends AbstractListComponent
{
    /** @var array<string, array{type: string, label: string, choices?: array<string, string>}>|null */
    private ?array $definitions = null;

    public function __construct(
        private readonly WptGroupRepository $groupRepository,
        private readonly TeacherRepository $teacherRepository,
    ) {
    }

    /** Consulta de lo que la persona puede ver, sin los filtros propios. */
    private function visibleQueryBuilder(): QueryBuilder
    {
        return TrackingController::generateAgreementQueryBuilder(
            $this->groupRepository,
            $this->teacherRepository,
            $this->academicYear,
            $this->newQueryBuilder(),
            $this->security->getUser(),
            $this->security->isGranted(OrganizationVoter::MANAGE, $this->organization()),
            $this->q !== '' ? $this->q : null,
        );
    }

    /** @return array<string, string> */
    private function choices(string $idExpression, string $labelExpression): array
    {
        $queryBuilder = $this->visibleQueryBuilder()
            ->select($idExpression . ' AS id', $labelExpression . ' AS label')
            ->distinct()
            ->resetDQLPart('groupBy')
            ->resetDQLPart('orderBy')
            ->orderBy('label');

        $choices = [];
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $choices[(string) $row['id']] = (string) $row['label'];
        }

        return $choices;
    }

    public function filterDefinitions(): array
    {
        return $this->definitions ??= [
            'shift' => ['type' => 'select', 'label' => $this->translator->trans('header.shift', [], 'wpt_tracking'), 'choices' => $this->choices('shi.id', 'shi.name')],
            'group' => ['type' => 'select', 'label' => $this->translator->trans('header.group', [], 'wpt_tracking'), 'choices' => $this->choices('g.id', 'g.name')],
        ];
    }

    protected function sortableColumns(): array
    {
        return [
            'student' => ['p.lastName', 'p.firstName'],
            'shift' => 'shi.name',
            'workcenter' => 'c.name',
            'dates' => 'a.startDate',
        ];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->visibleQueryBuilder();
        if (($shift = $this->filter('shift')) !== '') {
            $queryBuilder->andWhere('shi.id = :filter_shift')->setParameter('filter_shift', (int) $shift);
        }
        if (($group = $this->filter('group')) !== '') {
            $queryBuilder->andWhere('g.id = :filter_group')->setParameter('filter_group', (int) $group);
        }

        return $queryBuilder;
    }
}
