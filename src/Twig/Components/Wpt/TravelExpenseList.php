<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\Edu\Teacher;
use App\Entity\WptModule\TravelExpense;
use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use App\Security\WptModule\TravelExpenseVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Gastos de desplazamiento de un docente de FCT, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:TravelExpenseList', template: 'components/Wpt/TravelExpenseList.html.twig')]
final class TravelExpenseList extends AbstractListComponent
{
    #[LiveProp]
    public Teacher $teacher;

    public function allowNew(): bool
    {
        return $this->security->isGranted(WptOrganizationVoter::WPT_CREATE_EXPENSE, $this->organization())
            && $this->security->isGranted(TravelExpenseVoter::MANAGE, (new TravelExpense())->setTeacher($this->teacher));
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        $this->denyUnlessGranted(WptOrganizationVoter::WPT_ACCESS_EXPENSE, $organization);
        if ($this->teacher->getAcademicYear()->getOrganization() !== $organization) {
            throw new AccessDeniedException();
        }
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('te')
            ->addSelect('tr')
            ->addSelect('COUNT(a)')
            ->distinct()
            ->from(TravelExpense::class, 'te')
            ->join('te.travelRoute', 'tr')
            ->leftJoin('te.agreements', 'a')
            ->groupBy('te')
            ->addOrderBy('te.fromDateTime', 'DESC');

        if ($this->q !== '') {
            $queryBuilder
                ->orWhere('a.name LIKE :tq')
                ->orWhere('tr.description LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('te.teacher = :teacher')
            ->setParameter('teacher', $this->teacher);
    }
}
