<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Informes finales del alumnado de FCT por curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:FinalReportList', template: 'components/Wpt/FinalReportList.html.twig')]
final class FinalReportList extends AbstractAgreementEnrollmentList
{
    protected function authorize(): void
    {
        $this->denyUnlessGranted(WptOrganizationVoter::WPT_FILL_REPORT, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }
}
