<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\AcademicYear;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Cursos académicos del centro. */
#[AsLiveComponent('Organization:AcademicYearList', template: 'components/Organization/AcademicYearList.html.twig')]
final class AcademicYearList extends AbstractOrganizationList
{
    public function current(): ?AcademicYear
    {
        return $this->organization()->getCurrentAcademicYear();
    }

    protected function sortableColumns(): array
    {
        return ['description' => 'ay.description'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('ay')
            ->from(AcademicYear::class, 'ay')
            ->leftJoin('ay.financialManager', 'fm')
            ->leftJoin('fm.person', 'fmp')
            ->leftJoin('ay.principal', 'p')
            ->leftJoin('p.person', 'pp')
            ->orderBy('ay.description', 'DESC');

        if ($this->q !== '') {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX(
                    'ay.description LIKE :tq',
                    'pp.firstName LIKE :tq',
                    'pp.lastName LIKE :tq',
                    'fmp.firstName LIKE :tq',
                    'fmp.lastName LIKE :tq',
                ))
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('ay.organization = :organization')
            ->setParameter('organization', $this->organization());
    }
}
