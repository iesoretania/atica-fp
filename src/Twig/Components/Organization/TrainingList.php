<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\Training;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Enseñanzas de un curso académico. */
#[AsLiveComponent('Organization:TrainingList', template: 'components/Organization/TrainingList.html.twig')]
final class TrainingList extends AbstractAcademicYearList
{
    protected function sortableColumns(): array
    {
        return ['name' => 't.name'];
    }

    /** La tabla muestra los cursos de cada enseñanza: se cargan de una vez. */
    protected function preload(array $items): void
    {
        $this->newQueryBuilder()
            ->select('t', 'g')
            ->from(Training::class, 't')
            ->leftJoin('t.grades', 'g')
            ->where('t IN (:trainings)')
            ->setParameter('trainings', $items)
            ->getQuery()
            ->getResult();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('t', 'd')
            ->from(Training::class, 't')
            ->leftJoin('t.department', 'd')
            ->orderBy('t.name');

        if ($this->q !== '') {
            $queryBuilder->where('t.name LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
