<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\StudentEnrollment;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Alumnado matriculado en un curso académico. */
#[AsLiveComponent('Organization:StudentEnrollmentList', template: 'components/Organization/StudentEnrollmentList.html.twig')]
final class StudentEnrollmentList extends AbstractAcademicYearList
{
    protected function sortableColumns(): array
    {
        return ['name' => ['p.lastName', 'p.firstName'], 'group' => 'g.name'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('se', 'p', 'g')
            ->from(StudentEnrollment::class, 'se')
            ->innerJoin('se.person', 'p')
            ->innerJoin('se.group', 'g')
            ->innerJoin('g.grade', 'gr')
            ->innerJoin('gr.training', 't')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName');

        if ($this->q !== '') {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX(
                    'p.loginUsername LIKE :tq',
                    'p.firstName LIKE :tq',
                    'p.lastName LIKE :tq',
                    'p.emailAddress LIKE :tq',
                    'g.name LIKE :tq',
                    'p.uniqueIdentifier LIKE :tq',
                ))
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
