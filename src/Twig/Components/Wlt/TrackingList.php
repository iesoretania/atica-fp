<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Person;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\WltModule\TrackingQueryFactory;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Seguimiento de convenios de FP dual de un curso académico, con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:TrackingList', template: 'components/Wlt/TrackingList.html.twig')]
final class TrackingList extends AbstractListComponent
{
    private TrackingQueryFactory $trackingQueryFactory;

    #[Required]
    public function setTrackingQueryFactory(TrackingQueryFactory $trackingQueryFactory): void
    {
        $this->trackingQueryFactory = $trackingQueryFactory;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_ACCESS, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function sortableColumns(): array
    {
        return [
            'student' => ['p.lastName', 'p.firstName'],
            'project' => 'pro.name',
            'workcenter' => ['c.name', 'w.name'],
            'start' => 'a.startDate',
        ];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $organization = $this->organization();
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }

        return $this->trackingQueryFactory->create(
            $this->academicYear,
            $person,
            $this->security->isGranted(OrganizationVoter::MANAGE, $organization),
            $this->security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization),
            $this->q
        );
    }
}
