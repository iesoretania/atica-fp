<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Person;
use App\Repository\WltModule\StudentAnsweredSurveyRepository;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Encuestas del alumnado de FP dual por curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wlt:SurveyStudentList', template: 'components/Wlt/SurveyStudentList.html.twig')]
final class SurveyStudentList extends AbstractListComponent
{
    private StudentAnsweredSurveyRepository $repository;

    #[Required]
    public function setRepository(StudentAnsweredSurveyRepository $repository): void
    {
        $this->repository = $repository;
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_ACCESS, $organization);
        // quienes no participan en la encuesta del alumnado (tutoría laboral, profesorado del grupo) no la ven
        if (!$this->security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization)
            && !$this->security->isGranted(WltOrganizationVoter::WLT_DEPARTMENT_HEAD, $organization)
            && !$this->security->isGranted(WltOrganizationVoter::WLT_GROUP_TUTOR, $organization)
            && !$this->security->isGranted(WltOrganizationVoter::WLT_EDUCATIONAL_TUTOR, $organization)
            && !$this->security->isGranted(WltOrganizationVoter::WLT_STUDENT, $organization)
        ) {
            throw new AccessDeniedException();
        }
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }

        return $this->repository->findByAcademicYearAndPersonFilterQueryBuilder(
            $this->q !== '' ? $this->q : null,
            $this->academicYear,
            $person
        );
    }
}
