<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\PerformanceScale;
use App\Entity\Edu\PerformanceScaleValue;
use App\Security\Edu\PerformanceScaleVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Valores de una escala de valoración. */
#[AsLiveComponent('Organization:PerformanceScaleValueList', template: 'components/Organization/PerformanceScaleValueList.html.twig')]
final class PerformanceScaleValueList extends AbstractListComponent
{
    #[LiveProp]
    public PerformanceScale $performanceScale;

    protected function authorize(): void
    {
        if ($this->performanceScale->getOrganization() !== $this->organization()) {
            throw new AccessDeniedException();
        }
        $this->denyUnlessGranted(PerformanceScaleVoter::MANAGE, $this->performanceScale);
    }

    protected function sortableColumns(): array
    {
        return ['grade' => 'psv.description', 'numeric' => 'psv.numericGrade'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('psv')
            ->from(PerformanceScaleValue::class, 'psv')
            ->orderBy('psv.numericGrade', 'DESC');

        if ($this->q !== '') {
            $search = $queryBuilder->expr()->orX('psv.description LIKE :tq', 'psv.notes LIKE :tq');
            // la nota numérica solo se compara si lo buscado es un número
            if (is_numeric($this->q)) {
                $search->add('psv.numericGrade = :q');
                $queryBuilder->setParameter('q', (int) $this->q);
            }
            $queryBuilder->andWhere($search)->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('psv.performanceScale = :performance_scale')
            ->setParameter('performance_scale', $this->performanceScale);
    }
}
