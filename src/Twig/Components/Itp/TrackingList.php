<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\Person;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Seguimiento de las estancias de FFEOE de un curso académico, con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Itp:TrackingList', template: 'components/Itp/TrackingList.html.twig')]
final class TrackingList extends AbstractListComponent
{
    private StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository;

    #[Required]
    public function setStudentProgramWorkcenterRepository(StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository): void
    {
        $this->studentProgramWorkcenterRepository = $studentProgramWorkcenterRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_TRACKING, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function sortableColumns(): array
    {
        return [
            'student' => ['p.lastName', 'p.firstName'],
            'workcenter' => ['c.name', 'w.name'],
            'start' => 'spw.startDate',
        ];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }

        return $this->studentProgramWorkcenterRepository->createTrackingQueryBuilder(
            $this->academicYear,
            $person,
            $this->security->isGranted(OrganizationVoter::MANAGE, $this->organization()),
            $this->q !== '' ? $this->q : null
        );
    }
}
