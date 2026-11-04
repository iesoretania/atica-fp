<?php

declare(strict_types=1);

namespace App\Twig\Components\Company;

use App\Entity\Company;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Empresas colaboradoras (compartidas por todos los centros). */
#[AsLiveComponent('Company:CompanyList', template: 'components/Company/CompanyList.html.twig')]
final class CompanyList extends AbstractListComponent
{
    protected function authorize(): void
    {
        $this->denyUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $this->organization());
    }

    protected function sortableColumns(): array
    {
        return ['code' => 'c.code', 'name' => 'c.name', 'city' => 'c.city'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('c', 'm')
            ->from(Company::class, 'c')
            ->leftJoin('c.manager', 'm')
            ->orderBy('c.name')
            ->addOrderBy('c.city');

        if ($this->q !== '') {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX('c.code LIKE :tq', 'c.name LIKE :tq', 'c.city LIKE :tq', 'c.emailAddress LIKE :tq'))
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder;
    }
}
