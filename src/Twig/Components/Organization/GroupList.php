<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\Group;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Grupos de un curso académico. */
#[AsLiveComponent('Organization:GroupList', template: 'components/Organization/GroupList.html.twig')]
final class GroupList extends AbstractAcademicYearList
{
    protected function sortableColumns(): array
    {
        return ['name' => 'g.name', 'grade' => 'gr.name'];
    }

    /** La tabla muestra la tutoría y el alumnado de cada grupo: se cargan de una vez con sus personas. */
    protected function preload(array $items): void
    {
        $this->newQueryBuilder()
            ->select('g', 'tu', 'tup')
            ->from(Group::class, 'g')
            ->leftJoin('g.tutors', 'tu')
            ->leftJoin('tu.person', 'tup')
            ->where('g IN (:groups)')
            ->setParameter('groups', $items)
            ->getQuery()
            ->getResult();
        $this->newQueryBuilder()
            ->select('g', 'se', 'sep')
            ->from(Group::class, 'g')
            ->leftJoin('g.enrollments', 'se')
            ->leftJoin('se.person', 'sep')
            ->where('g IN (:groups)')
            ->setParameter('groups', $items)
            ->getQuery()
            ->getResult();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('g', 'gr', 't')
            ->from(Group::class, 'g')
            ->innerJoin('g.grade', 'gr')
            ->innerJoin('gr.training', 't')
            ->orderBy('g.name');

        if ($this->q !== '') {
            $queryBuilder
                ->where('g.name LIKE :tq')
                ->orWhere('gr.name LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
