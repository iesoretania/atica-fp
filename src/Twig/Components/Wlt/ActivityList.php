<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\WltModule\Activity;
use App\Entity\WltModule\Project;
use App\Security\WltModule\ProjectVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Actividades del programa formativo de un proyecto de FP dual, con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:ActivityList', template: 'components/Wlt/ActivityList.html.twig')]
final class ActivityList extends AbstractListComponent
{
    #[LiveProp]
    public Project $project;

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ProjectVoter::MANAGE, $this->project);
    }

    /** Con el proyecto bloqueado, la consulta es de solo lectura. */
    public function isReadOnly(): bool
    {
        return !$this->security->isGranted(ProjectVoter::MODIFY, $this->project);
    }

    protected function sortableColumns(): array
    {
        return ['code' => 'a.code'];
    }

    /** La tabla muestra las competencias de cada actividad: se cargan de una vez. */
    protected function preload(array $items): void
    {
        $this->newQueryBuilder()
            ->select('a', 'co')
            ->from(Activity::class, 'a')
            ->leftJoin('a.competencies', 'co')
            ->where('a IN (:activities)')
            ->setParameter('activities', $items)
            ->getQuery()
            ->getResult();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('a')
            ->from(Activity::class, 'a')
            ->orderBy('a.code');

        if ($this->q !== '') {
            $queryBuilder
                ->where('a.code LIKE :tq')
                ->orWhere('a.description LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('a.project = :project')
            ->setParameter('project', $this->project);
    }
}
