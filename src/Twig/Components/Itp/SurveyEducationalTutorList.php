<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\Person;
use App\Repository\ItpModule\EducationalTutorAnsweredSurveyRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Encuestas de la tutoría docente de FFEOE por curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Itp:SurveyEducationalTutorList', template: 'components/Itp/SurveyEducationalTutorList.html.twig')]
final class SurveyEducationalTutorList extends AbstractListComponent
{
    private EducationalTutorAnsweredSurveyRepository $repository;

    #[Required]
    public function setRepository(EducationalTutorAnsweredSurveyRepository $repository): void
    {
        $this->repository = $repository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_EDUCATIONAL_TUTOR, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }
        $organization = $this->organization();

        return $this->repository->createStatsByAcademicYearAndPersonFilterQueryBuilder(
            $this->academicYear,
            $this->security->isGranted(OrganizationVoter::MANAGE, $organization),
            $this->security->isGranted(ItpOrganizationVoter::ITP_MANAGER, $organization),
            $person,
            $this->q !== '' ? $this->q : null
        );
    }
}
