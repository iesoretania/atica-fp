<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Person;
use App\Repository\WltModule\TeacherRepository;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Encuestas de la tutoría docente de FP dual por curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wlt:SurveyEducationalTutorList', template: 'components/Wlt/SurveyEducationalTutorList.html.twig')]
final class SurveyEducationalTutorList extends AbstractListComponent
{
    private TeacherRepository $wltTeacherRepository;

    #[Required]
    public function setRepository(TeacherRepository $wltTeacherRepository): void
    {
        $this->wltTeacherRepository = $wltTeacherRepository;
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        // tutores docentes y coordinación, y también los jefes de departamento, como en FCT y la FFEOE
        if (!$this->security->isGranted(WltOrganizationVoter::WLT_EDUCATIONAL_TUTOR, $organization)
            && !$this->security->isGranted(WltOrganizationVoter::WLT_DEPARTMENT_HEAD, $organization)) {
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

        return $this->wltTeacherRepository->findTeachersDataByProjectGroupByProjectAndPersonFilteredQueryBuilder(
            $this->q !== '' ? $this->q : null,
            $this->academicYear,
            $person
        );
    }
}
