<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\Subject;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Módulos profesionales de un curso académico. */
#[AsLiveComponent('Organization:SubjectList', template: 'components/Organization/SubjectList.html.twig')]
final class SubjectList extends AbstractAcademicYearList
{
    protected function sortableColumns(): array
    {
        return ['name' => 's.name', 'grade' => 'g.name'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('s', 'g')
            ->from(Subject::class, 's')
            ->join('s.grade', 'g')
            ->join('g.training', 't')
            ->orderBy('s.name');

        if ($this->q !== '') {
            $queryBuilder
                ->where('s.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
