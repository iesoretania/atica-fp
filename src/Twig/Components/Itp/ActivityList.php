<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\ItpModule\ProgramGrade;
use App\Repository\ItpModule\ActivityRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\TrainingProgramVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Actividades formativas de un programa formativo de FFEOE (por ciclo), con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Itp:ActivityList', template: 'components/Itp/ActivityList.html.twig')]
final class ActivityList extends AbstractListComponent
{
    #[LiveProp]
    public ProgramGrade $programGrade;

    private ActivityRepository $activityRepository;

    #[Required]
    public function setActivityRepository(ActivityRepository $activityRepository): void
    {
        $this->activityRepository = $activityRepository;
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);
        $this->denyUnlessGranted(TrainingProgramVoter::MANAGE, $this->programGrade->getTrainingProgram());
        if ($this->programGrade->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $organization) {
            throw new AccessDeniedException();
        }
    }

    protected function fetchJoinCollection(): bool
    {
        return true;
    }

    protected function sortableColumns(): array
    {
        return ['code' => 'a.code', 'name' => 'a.name'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->activityRepository->createActivityByProgramGradeQueryBuilder($this->programGrade, $this->q !== '' ? $this->q : null);
    }
}
