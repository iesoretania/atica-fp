<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\NonWorkingDay;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Días no lectivos de un curso académico. */
#[AsLiveComponent('Organization:NonWorkingDayList', template: 'components/Organization/NonWorkingDayList.html.twig')]
final class NonWorkingDayList extends AbstractAcademicYearList
{
    protected function sortableColumns(): array
    {
        return ['date' => 'n.date', 'description' => 'n.description'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('n')
            ->from(NonWorkingDay::class, 'n')
            ->orderBy('n.date');

        if ($this->q !== '') {
            $queryBuilder->where('n.description LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('n.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
