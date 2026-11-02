<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\PerformanceScale;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Escalas de valoración del desempeño del centro. */
#[AsLiveComponent('Organization:PerformanceScaleList', template: 'components/Organization/PerformanceScaleList.html.twig')]
final class PerformanceScaleList extends AbstractOrganizationList
{
    protected function sortableColumns(): array
    {
        return ['description' => 'ps.description'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('ps')
            ->from(PerformanceScale::class, 'ps')
            ->orderBy('ps.description');

        if ($this->q !== '') {
            $queryBuilder->where('ps.description LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('ps.organization = :organization')
            ->setParameter('organization', $this->organization());
    }
}
