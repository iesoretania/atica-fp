<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use App\Entity\Organization;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Centros educativos (solo administración global). */
#[AsLiveComponent('Admin:OrganizationList', template: 'components/Admin/OrganizationList.html.twig')]
final class OrganizationList extends AbstractListComponent
{
    protected function authorize(): void
    {
        $this->denyUnlessGranted('ROLE_ADMIN');
    }

    public function currentOrganizationId(): ?int
    {
        return $this->organization()->getId();
    }

    protected function sortableColumns(): array
    {
        return ['name' => 'o.name', 'code' => 'o.code', 'city' => 'o.city'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('o')
            ->from(Organization::class, 'o')
            ->orderBy('o.name');

        if ($this->q !== '') {
            $search = $queryBuilder->expr()->orX(
                'o.name LIKE :tq',
                'o.code LIKE :tq',
                'o.emailAddress LIKE :tq',
                'o.phoneNumber LIKE :tq',
                'o.city LIKE :tq',
            );
            if (ctype_digit($this->q)) {
                $search->add('o.id = :q');
                $queryBuilder->setParameter('q', (int) $this->q);
            }
            $queryBuilder->andWhere($search)->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder;
    }
}
