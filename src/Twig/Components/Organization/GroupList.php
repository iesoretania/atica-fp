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
