<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\Person;
use App\Repository\ItpModule\ProgramGroupRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Grupos con estancias que puede evaluar la persona conectada, por curso académico. */
#[AsLiveComponent('Itp:GradingGroupList', template: 'components/Itp/GradingGroupList.html.twig')]
final class GradingGroupList extends AbstractListComponent
{
    private ProgramGroupRepository $programGroupRepository;
    private StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository;

    #[Required]
    public function setRepositories(ProgramGroupRepository $programGroupRepository, StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository): void
    {
        $this->programGroupRepository = $programGroupRepository;
        $this->studentProgramWorkcenterRepository = $studentProgramWorkcenterRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_VIEW_EVALUATION, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function fetchJoinCollection(): bool
    {
        return true;
    }

    protected function sortableColumns(): array
    {
        return ['group' => 'g.name', 'department' => 'd.name'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $this->organization());

        $studentProgramWorkcenters = $this->studentProgramWorkcenterRepository->findByAcademicYearPersonManagerAndQuery($this->academicYear, $person, $isManager, null, true);

        return $this->programGroupRepository->createGroupsFromStudentProgramWorkcenterStatsQueryBuilder($studentProgramWorkcenters, $this->q !== '' ? $this->q : null);
    }
}
