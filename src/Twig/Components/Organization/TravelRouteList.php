<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\TravelRoute;
use App\Security\Edu\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Rutas de desplazamiento del centro. */
#[AsLiveComponent('Organization:TravelRouteList', template: 'components/Organization/TravelRouteList.html.twig')]
final class TravelRouteList extends AbstractListComponent
{
    protected function authorize(): void
    {
        $this->denyUnlessGranted(OrganizationVoter::EDU_FINANCIAL_MANAGER, $this->organization());
    }

    protected function sortableColumns(): array
    {
        return ['description' => 'tr.description', 'distance' => 'tr.distance', 'verified' => 'tr.verified'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('tr')
            ->from(TravelRoute::class, 'tr')
            ->orderBy('tr.description');

        if ($this->q !== '') {
            $queryBuilder->andWhere('tr.description LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('tr.organization = :organization')
            ->setParameter('organization', $this->organization());
    }
}
