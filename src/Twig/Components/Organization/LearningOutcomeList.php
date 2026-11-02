<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\Subject;
use App\Security\Edu\TrainingVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Resultados de aprendizaje de un módulo profesional (TrainingVoter ya exige que sea del centro actual). */
#[AsLiveComponent('Organization:LearningOutcomeList', template: 'components/Organization/LearningOutcomeList.html.twig')]
final class LearningOutcomeList extends AbstractListComponent
{
    #[LiveProp]
    public Subject $subject;

    protected function authorize(): void
    {
        $this->denyUnlessGranted(TrainingVoter::MANAGE, $this->subject->getGrade()->getTraining());
    }

    protected function sortableColumns(): array
    {
        return ['code' => 'l.code'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('l')
            ->from(LearningOutcome::class, 'l')
            ->orderBy('l.code');

        if ($this->q !== '') {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX('l.code LIKE :tq', 'l.description LIKE :tq'))
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('l.subject = :subject')
            ->setParameter('subject', $this->subject);
    }
}
