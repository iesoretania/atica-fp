<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\Edu\Teacher;
use App\Entity\WptModule\Contact;
use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use App\Service\WptModule\VisitTeacherProvider;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Resumen de visitas por docente de FCT en un curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:VisitTeacherList', template: 'components/Wpt/VisitTeacherList.html.twig')]
final class VisitTeacherList extends AbstractListComponent
{
    public function __construct(private readonly VisitTeacherProvider $teacherProvider)
    {
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WptOrganizationVoter::WPT_ACCESS_VISIT, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('t')
            ->addSelect('p')
            ->addSelect('COUNT(v)')
            ->addSelect('COUNT(DISTINCT a)')
            ->from(Teacher::class, 't')
            ->join('t.person', 'p')
            ->leftJoin(Contact::class, 'v', 'WITH', 'v.teacher = t')
            ->leftJoin('v.agreements', 'a')
            ->groupBy('t')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName');

        if ($this->q !== '') {
            $queryBuilder
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('t IN (:teachers)')
            ->setParameter('teachers', $this->teacherProvider->forAcademicYear($this->academicYear, false));
    }
}
