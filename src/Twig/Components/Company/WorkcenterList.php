<?php

declare(strict_types=1);

namespace App\Twig\Components\Company;

use App\Entity\Company;
use App\Entity\Workcenter;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Centros de trabajo de una empresa colaboradora. */
#[AsLiveComponent('Company:WorkcenterList', template: 'components/Company/WorkcenterList.html.twig')]
final class WorkcenterList extends AbstractListComponent
{
    #[LiveProp]
    public Company $company;

    protected function authorize(): void
    {
        $this->denyUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $this->organization());
    }

    protected function sortableColumns(): array
    {
        return ['name' => 'w.name', 'city' => 'w.city'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('w')
            ->from(Workcenter::class, 'w')
            ->orderBy('w.name')
            ->addOrderBy('w.city');

        if ($this->q !== '') {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX('w.name LIKE :tq', 'w.city LIKE :tq', 'w.emailAddress LIKE :tq'))
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('w.company = :company')
            ->setParameter('company', $this->company);
    }
}
