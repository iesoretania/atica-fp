<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WltModule\TravelExpense;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WltModule\TeacherRepository as WltTeacherRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Resumen de gastos de desplazamiento por docente en FP dual de un curso académico. */
#[AsLiveComponent('Wlt:TravelExpenseTeacherList', template: 'components/Wlt/TravelExpenseTeacherList.html.twig')]
final class TravelExpenseTeacherList extends AbstractListComponent
{
    private TeacherRepository $teacherRepository;
    private WltTeacherRepository $wltTeacherRepository;

    #[Required]
    public function setRepositories(TeacherRepository $teacherRepository, WltTeacherRepository $wltTeacherRepository): void
    {
        $this->teacherRepository = $teacherRepository;
        $this->wltTeacherRepository = $wltTeacherRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_ACCESS_EXPENSE, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function sortableColumns(): array
    {
        return ['teacher' => ['p.lastName', 'p.firstName']];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $organization = $this->organization();
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }

        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization)
            || $this->security->isGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        $queryBuilder = $this->newQueryBuilder()
            ->select('t', 'p')
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

        $teachers = !$isManager
            ? [$this->teacherRepository->findOneByPersonAndAcademicYear($person, $this->academicYear)]
            : $this->wltTeacherRepository->findByAcademicYear($this->academicYear);

        return $queryBuilder
            ->andWhere('t IN (:teachers)')
            ->setParameter('teachers', $teachers);
    }
}
