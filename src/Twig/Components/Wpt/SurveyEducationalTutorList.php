<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Repository\Edu\TeacherRepository;
use App\Repository\WptModule\TeacherRepository as WptTeacherRepository;
use App\Security\WptModule\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Encuestas de la tutoría docente de FCT por curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:SurveyEducationalTutorList', template: 'components/Wpt/SurveyEducationalTutorList.html.twig')]
final class SurveyEducationalTutorList extends AbstractListComponent
{
    public function __construct(
        private readonly TeacherRepository $teacherRepository,
        private readonly WptTeacherRepository $wptTeacherRepository,
    ) {
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        $this->denyUnlessAcademicYearIsCurrentOrganizations();

        if (
            !$this->security->isGranted(OrganizationVoter::WPT_EDUCATIONAL_TUTOR, $organization)
            && !$this->security->isGranted(OrganizationVoter::WPT_MANAGER, $organization)
        ) {
            throw new AccessDeniedException();
        }
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $teacher = $this->teacherRepository->findOneByPersonAndAcademicYear($this->security->getUser(), $this->academicYear);

        return $this->wptTeacherRepository->findTeachersShiftDataByAcademicYearAndTeacherFilteredQueryBuilder(
            $this->q !== '' ? $this->q : null,
            $this->academicYear,
            $teacher,
        );
    }
}
