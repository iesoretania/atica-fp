<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Repository\WltModule\ContactRepository;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\WltModule\ContactReportTeacherProvider;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Resumen de contactos por docente de seguimiento (entrada a su informe), con filtro y paginación dinámicos. */
#[AsLiveComponent('Wlt:ContactEducationalTutorReportList', template: 'components/Wlt/ContactEducationalTutorReportList.html.twig')]
final class ContactEducationalTutorReportList extends AbstractListComponent
{
    private ContactRepository $contactRepository;
    private ContactReportTeacherProvider $reportTeachers;

    #[Required]
    public function setServices(ContactRepository $contactRepository, ContactReportTeacherProvider $reportTeachers): void
    {
        $this->contactRepository = $contactRepository;
        $this->reportTeachers = $reportTeachers;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_ACCESS_VISIT, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->contactRepository->getTeacherStatsByIdAndFilterQueryBuilder(
            $this->reportTeachers->forAcademicYear($this->academicYear),
            $this->q !== '' ? $this->q : null
        );
    }
}
