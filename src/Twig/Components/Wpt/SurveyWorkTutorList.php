<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\WptModule\WorkTutorAnsweredSurvey;
use App\Repository\WptModule\AgreementEnrollmentRepository;
use App\Security\WptModule\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Encuestas de la tutoría laboral de FCT por curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:SurveyWorkTutorList', template: 'components/Wpt/SurveyWorkTutorList.html.twig')]
final class SurveyWorkTutorList extends AbstractListComponent
{
    public function __construct(private readonly AgreementEnrollmentRepository $repository)
    {
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        $this->denyUnlessAcademicYearIsCurrentOrganizations();

        foreach ([OrganizationVoter::WPT_MANAGER, OrganizationVoter::WPT_WORK_TUTOR, OrganizationVoter::WPT_EDUCATIONAL_TUTOR, OrganizationVoter::WPT_GROUP_TUTOR] as $role) {
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
        )
            ->leftJoin(
                WorkTutorAnsweredSurvey::class,
                'was',
                'WITH',
                '(was.workTutor = wt OR was.workTutor = awt) AND was.shift = shi'
            )
            ->addSelect('COUNT(was), awt')
            ->addGroupBy('ae')
            ->addOrderBy('wt.lastName')
            ->addOrderBy('wt.firstName')
            ->addOrderBy('wt.id')
            ->addOrderBy('awt.id', 'DESC')
            ->addOrderBy('shi.name');
    }
}
