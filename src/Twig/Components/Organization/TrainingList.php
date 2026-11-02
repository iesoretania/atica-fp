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

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('t')
            ->from(Training::class, 't')
            ->orderBy('t.name');

        if ($this->q !== '') {
            $queryBuilder->where('t.name LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
