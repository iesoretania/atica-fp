<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Seguimiento del alumnado de FCT por curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:TrackingList', template: 'components/Wpt/TrackingList.html.twig')]
final class TrackingList extends AbstractAgreementEnrollmentList
{
    protected function authorize(): void
    {
        $this->denyUnlessGranted(WptOrganizationVoter::WPT_ACCESS, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }
}
