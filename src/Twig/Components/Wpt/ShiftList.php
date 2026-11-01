<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

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

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('s')
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

        return $queryBuilder
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
