<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\WptModule\Agreement;
use App\Entity\WptModule\Shift;
use App\Repository\WptModule\AgreementEnrollmentRepository;
use App\Security\WptModule\ShiftVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Listado de acuerdos de colaboración de una convocatoria de FCT, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:AgreementList', template: 'components/Wpt/AgreementList.html.twig')]
final class AgreementList extends AbstractListComponent
{
    #[LiveProp]
    public Shift $shift;

    public function __construct(private readonly AgreementEnrollmentRepository $repository)
    {
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ShiftVoter::MANAGE, $this->shift);
        if ($this->shift->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $this->organization()) {
            throw new AccessDeniedException();
        }
    }

    /** @var array<string, array{type: string, label: string, choices?: array<string, string>}>|null */
    private ?array $definitions = null;

    public function filterDefinitions(): array
    {
        return $this->definitions ??= [
            'workcenter' => [
                'type' => 'select',
                'label' => $this->translator->trans('header.workcenter', [], 'wpt_agreement'),
                'choices' => $this->workcenterChoices(),
            ],
        ];
    }

    /** Solo se ofrecen los centros de trabajo que tienen acuerdos en esta convocatoria. */
    private function workcenterChoices(): array
    {
        $rows = $this->newQueryBuilder()
            ->select('w.id AS id', 'c.name AS company', 'w.name AS name')
            ->distinct()
            ->from(Agreement::class, 'a')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->where('a.shift = :shift')
            ->setParameter('shift', $this->shift)
            ->orderBy('c.name')
            ->addOrderBy('w.name')
            ->getQuery()
            ->getArrayResult();

        $choices = [];
        foreach ($rows as $row) {
            $choices[(string) $row['id']] = $row['company'] . ' - ' . $row['name'];
        }

        return $choices;
    }

    // solo columnas del propio acuerdo: las filas de un mismo acuerdo deben quedar juntas (celdas combinadas)
    protected function sortableColumns(): array
    {
        return ['workcenter' => 'c.name', 'dates' => 'a.startDate'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->findByShiftAndFilter($this->shift, $this->q !== '' ? $this->q : null);
        if (($workcenter = $this->filter('workcenter')) !== '') {
            $queryBuilder->andWhere('w.id = :filter_workcenter')->setParameter('filter_workcenter', (int) $workcenter);
        }

        return $queryBuilder;
    }
}
