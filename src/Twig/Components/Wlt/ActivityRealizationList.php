<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\WltModule\Activity;
use App\Entity\WltModule\ActivityRealization;
use App\Security\WltModule\ProjectVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Concreciones de una actividad de FP dual, con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:ActivityRealizationList', template: 'components/Wlt/ActivityRealizationList.html.twig')]
final class ActivityRealizationList extends AbstractListComponent
{
    #[LiveProp]
    public Activity $activity;

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ProjectVoter::MANAGE, $this->activity->getProject());
    }

    public function isReadOnly(): bool
    {
        return !$this->security->isGranted(ProjectVoter::MODIFY, $this->activity->getProject());
    }

    protected function sortableColumns(): array
    {
        return ['code' => 'ar.code'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('ar')
            ->from(ActivityRealization::class, 'ar')
            ->orderBy('ar.code');

        if ($this->q !== '') {
            $queryBuilder
                ->where('ar.code LIKE :tq')
                ->orWhere('ar.description LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('ar.activity = :activity')
            ->setParameter('activity', $this->activity);
    }
}
