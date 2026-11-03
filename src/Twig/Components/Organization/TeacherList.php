<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\Teacher;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Profesorado de un curso académico. */
#[AsLiveComponent('Organization:TeacherList', template: 'components/Organization/TeacherList.html.twig')]
final class TeacherList extends AbstractAcademicYearList
{
    protected function sortableColumns(): array
    {
        return ['name' => ['p.lastName', 'p.firstName']];
    }

    /** La consulta trae también las asignaciones docentes de cada profesor (colección). */
    protected function fetchJoinCollection(): bool
    {
        return true;
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('t', 'p', 'te', 'su', 'g')
            ->from(Teacher::class, 't')
            ->innerJoin('t.person', 'p')
            ->leftJoin('t.teachings', 'te')
            ->leftJoin('te.subject', 'su')
            ->leftJoin('te.group', 'g')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName');

        if ($this->q !== '') {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX(
                    'p.loginUsername LIKE :tq',
                    'p.firstName LIKE :tq',
                    'p.lastName LIKE :tq',
                    'p.emailAddress LIKE :tq',
                    'p.uniqueIdentifier LIKE :tq',
                ))
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
