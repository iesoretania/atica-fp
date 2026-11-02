<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\Department;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Departamentos de un curso académico. */
#[AsLiveComponent('Organization:DepartmentList', template: 'components/Organization/DepartmentList.html.twig')]
final class DepartmentList extends AbstractAcademicYearList
{
    protected function sortableColumns(): array
    {
        return ['name' => 'd.name'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('d')
            ->from(Department::class, 'd')
            ->orderBy('d.name');

        if ($this->q !== '') {
            $queryBuilder->where('d.name LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('d.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
