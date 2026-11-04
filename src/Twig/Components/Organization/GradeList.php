<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\Grade;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Cursos (niveles) de un curso académico. */
#[AsLiveComponent('Organization:GradeList', template: 'components/Organization/GradeList.html.twig')]
final class GradeList extends AbstractAcademicYearList
{
    protected function sortableColumns(): array
    {
        return ['name' => 'g.name', 'training' => 't.name'];
    }

    /** La tabla muestra los grupos de cada curso: se cargan de una vez. */
    protected function preload(array $items): void
    {
        $this->newQueryBuilder()
            ->select('g', 'gr')
            ->from(Grade::class, 'g')
            ->leftJoin('g.groups', 'gr')
            ->where('g IN (:grades)')
            ->setParameter('grades', $items)
            ->getQuery()
            ->getResult();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('g', 't', 'd')
            ->from(Grade::class, 'g')
            ->innerJoin('g.training', 't')
            ->leftJoin('t.department', 'd')
            ->orderBy('g.name');

        if ($this->q !== '') {
            $queryBuilder
                ->where('g.name LIKE :tq')
                ->orWhere('t.name LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
