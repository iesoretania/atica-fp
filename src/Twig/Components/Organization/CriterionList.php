<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\Criterion;
use App\Entity\Edu\LearningOutcome;
use App\Security\Edu\TrainingVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Criterios de evaluación de un resultado de aprendizaje (TrainingVoter ya exige que sea del centro actual). */
#[AsLiveComponent('Organization:CriterionList', template: 'components/Organization/CriterionList.html.twig')]
final class CriterionList extends AbstractListComponent
{
    #[LiveProp]
    public LearningOutcome $learningOutcome;

    protected function authorize(): void
    {
        $this->denyUnlessGranted(TrainingVoter::MANAGE, $this->learningOutcome->getSubject()->getGrade()->getTraining());
    }

    protected function sortableColumns(): array
    {
        return ['code' => 'c.code'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('c')
            ->from(Criterion::class, 'c')
            ->orderBy('c.code');

        if ($this->q !== '') {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX('c.code LIKE :tq', 'c.name LIKE :tq', 'c.description LIKE :tq'))
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('c.learningOutcome = :learning_outcome')
            ->setParameter('learning_outcome', $this->learningOutcome);
    }
}
