<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Repository\WptModule\StudentAnsweredSurveyRepository;
use App\Security\WptModule\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Encuestas del alumnado de FCT por curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:SurveyStudentList', template: 'components/Wpt/SurveyStudentList.html.twig')]
final class SurveyStudentList extends AbstractListComponent
{
    public function __construct(private readonly StudentAnsweredSurveyRepository $repository)
    {
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        $this->denyUnlessGranted(OrganizationVoter::WPT_ACCESS, $organization);
        $this->denyUnlessAcademicYearIsCurrentOrganizations();

        // la tutoría laboral no participa en la encuesta del alumnado (igual que en el menú)
        foreach ([OrganizationVoter::WPT_MANAGER, OrganizationVoter::WPT_DEPARTMENT_HEAD, OrganizationVoter::WPT_EDUCATIONAL_TUTOR, OrganizationVoter::WPT_GROUP_TUTOR, OrganizationVoter::WPT_STUDENT] as $role) {
            if ($this->security->isGranted($role, $organization)) {
                return;
            }
        }
        throw new AccessDeniedException();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->repository->findByAcademicYearAndPersonFilterQueryBuilder(
            $this->q !== '' ? $this->q : null,
            $this->academicYear,
            $this->security->getUser(),
        );
    }
}
