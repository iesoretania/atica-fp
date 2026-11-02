<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Survey;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Encuestas del centro. */
#[AsLiveComponent('Organization:SurveyList', template: 'components/Organization/SurveyList.html.twig')]
final class SurveyList extends AbstractOrganizationList
{
    protected function sortableColumns(): array
    {
        return ['title' => 's.title', 'start' => 's.startTimestamp', 'end' => 's.endTimestamp'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('s')
            ->from(Survey::class, 's')
            ->orderBy('s.title');

        if ($this->q !== '') {
            $queryBuilder->where('s.title LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('s.organization = :organization')
            ->setParameter('organization', $this->organization());
    }
}
