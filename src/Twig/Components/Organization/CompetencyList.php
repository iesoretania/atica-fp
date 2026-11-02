<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\Competency;
use App\Entity\Edu\Training;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Competencias de una enseñanza. */
#[AsLiveComponent('Organization:CompetencyList', template: 'components/Organization/CompetencyList.html.twig')]
final class CompetencyList extends AbstractListComponent
{
    #[LiveProp]
    public Training $training;

    protected function authorize(): void
    {
        $organization = $this->training->getAcademicYear()->getOrganization();
        if ($organization !== $this->organization()) {
            throw new AccessDeniedException();
        }
        $this->denyUnlessGranted(OrganizationVoter::MANAGE, $organization);
    }

    protected function sortableColumns(): array
    {
        return ['code' => 'c.code'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('c')
            ->from(Competency::class, 'c')
            ->orderBy('c.code');

        if ($this->q !== '') {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX('c.code LIKE :tq', 'c.description LIKE :tq'))
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('c.training = :training')
            ->setParameter('training', $this->training);
    }
}
