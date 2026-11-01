<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\Person;
use App\Repository\ItpModule\WorkTutorAnsweredSurveyRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Encuestas de la tutoría laboral de FFEOE por curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Itp:SurveyWorkTutorList', template: 'components/Itp/SurveyWorkTutorList.html.twig')]
final class SurveyWorkTutorList extends AbstractListComponent
{
    private WorkTutorAnsweredSurveyRepository $repository;

    #[Required]
    public function setRepository(WorkTutorAnsweredSurveyRepository $repository): void
    {
        $this->repository = $repository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_WORK_TUTOR, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }

        return $this->repository->createStatsByAcademicYearAndPersonFilterQueryBuilder(
            $this->academicYear,
            $this->security->isGranted(OrganizationVoter::MANAGE, $this->organization()),
            $person,
            $this->q !== '' ? $this->q : null
        );
    }
}
