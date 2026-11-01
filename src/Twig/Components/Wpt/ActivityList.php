<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\WptModule\Activity;
use App\Entity\WptModule\Shift;
use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use App\Security\WptModule\ShiftVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Programa formativo (actividades) de una convocatoria de FCT, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:ActivityList', template: 'components/Wpt/ActivityList.html.twig')]
final class ActivityList extends AbstractListComponent
{
    #[LiveProp]
    public Shift $shift;

    protected function authorize(): void
    {
        $organization = $this->organization();
        $this->denyUnlessGranted(WptOrganizationVoter::WPT_MANAGER, $organization);
        $this->denyUnlessGranted(ShiftVoter::MANAGE, $this->shift);
        if ($this->shift->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $organization) {
            throw new AccessDeniedException();
        }
    }

    protected function sortableColumns(): array
    {
        return ['code' => 'a.code'];
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
            ->andWhere('a.shift = :shift')
            ->setParameter('shift', $this->shift);
    }
}
