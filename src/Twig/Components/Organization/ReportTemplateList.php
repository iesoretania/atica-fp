<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\ReportTemplate;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Plantillas de informe del centro. */
#[AsLiveComponent('Organization:ReportTemplateList', template: 'components/Organization/ReportTemplateList.html.twig')]
final class ReportTemplateList extends AbstractOrganizationList
{
    protected function sortableColumns(): array
    {
        return ['description' => 'rt.description'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('rt')
            ->from(ReportTemplate::class, 'rt')
            ->orderBy('rt.description');

        if ($this->q !== '') {
            $queryBuilder->where('rt.description LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('rt.organization = :organization')
            ->setParameter('organization', $this->organization());
    }
}
