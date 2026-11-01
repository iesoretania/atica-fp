<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\Edu\Teacher;
use App\Entity\WptModule\TravelExpense;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WptModule\TeacherRepository as WptTeacherRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Resumen de gastos de desplazamiento por docente de FCT en un curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:TravelExpenseTeacherList', template: 'components/Wpt/TravelExpenseTeacherList.html.twig')]
final class TravelExpenseTeacherList extends AbstractListComponent
{
    public function __construct(
        private readonly TeacherRepository $teacherRepository,
        private readonly WptTeacherRepository $wptTeacherRepository,
    ) {
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WptOrganizationVoter::WPT_ACCESS_EXPENSE, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $organization = $this->organization();
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization)
            || $this->security->isGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        $queryBuilder = $this->newQueryBuilder()
            ->select('t')
            ->addSelect('p')
            ->addSelect('COUNT(te)')
            ->addSelect('SUM(tr.distance)')
            ->addSelect('SUM(tr.verified)')
            ->addSelect('SUM(te.otherExpenses)')
            ->from(Teacher::class, 't')
            ->join('t.person', 'p')
            ->leftJoin(TravelExpense::class, 'te', 'WITH', 'te.teacher = t')
            ->leftJoin('te.travelRoute', 'tr')
            ->groupBy('t')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName');

        if ($this->q !== '') {
            $queryBuilder
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        if (!$isManager) {
            $teachers = [$this->teacherRepository->findOneByPersonAndAcademicYear($this->security->getUser(), $this->academicYear)];
        } else {
            $teachers = $this->wptTeacherRepository->findByAcademicYear($this->academicYear);
        }

        return $queryBuilder
            ->andWhere('t IN (:teachers)')
            ->setParameter('teachers', $teachers);
    }
}
