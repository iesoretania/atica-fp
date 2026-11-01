<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\WltModule\LearningProgram;
use App\Entity\WltModule\Project;
use App\Security\WltModule\ProjectVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Programa formativo por empresa de un proyecto de FP dual, con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:LearningProgramList', template: 'components/Wlt/LearningProgramList.html.twig')]
final class LearningProgramList extends AbstractListComponent
{
    #[LiveProp]
    public Project $project;

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ProjectVoter::MANAGE, $this->project);
    }

    public function isReadOnly(): bool
    {
        return !$this->security->isGranted(ProjectVoter::MODIFY, $this->project);
    }

    protected function sortableColumns(): array
    {
        return ['company' => 'c.name', 'items' => 'items'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('cp')
            ->addSelect('SIZE(cp.activityRealizations) AS HIDDEN items')
            ->from(LearningProgram::class, 'cp')
            ->join('cp.company', 'c')
            ->addOrderBy('c.name');

        if ($this->q !== '') {
            $queryBuilder
                ->where('c.name LIKE :tq')
                ->orWhere('c.code LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('cp.project = :project')
            ->setParameter('project', $this->project);
    }
}
