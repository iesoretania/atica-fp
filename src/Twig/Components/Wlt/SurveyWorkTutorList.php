<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Person;
use App\Entity\WltModule\WorkTutorAnsweredSurvey;
use App\Repository\WltModule\AgreementRepository;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Encuestas de la tutoría laboral de FP dual por curso académico, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wlt:SurveyWorkTutorList', template: 'components/Wlt/SurveyWorkTutorList.html.twig')]
final class SurveyWorkTutorList extends AbstractListComponent
{
    private AgreementRepository $agreementRepository;

    #[Required]
    public function setRepository(AgreementRepository $agreementRepository): void
    {
        $this->agreementRepository = $agreementRepository;
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_ACCESS, $organization);
        // quienes no participan en la encuesta de la empresa (alumnado, profesorado del grupo) no la ven
        if (!$this->security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization)
            && !$this->security->isGranted(WltOrganizationVoter::WLT_DEPARTMENT_HEAD, $organization)
            && !$this->security->isGranted(WltOrganizationVoter::WLT_GROUP_TUTOR, $organization)
            && !$this->security->isGranted(WltOrganizationVoter::WLT_EDUCATIONAL_TUTOR, $organization)
            && !$this->security->isGranted(WltOrganizationVoter::WLT_WORK_TUTOR, $organization)
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

        return $this->agreementRepository->findByAcademicYearAndPersonFilterQueryBuilder(
            $this->q !== '' ? $this->q : null,
            $this->academicYear,
            $person
        )
            ->leftJoin(
                WorkTutorAnsweredSurvey::class,
                'was',
                'WITH',
                '(was.workTutor = wt OR was.workTutor = awt) AND was.project = pro AND was.academicYear = :academic_year'
            )
            ->addSelect('COUNT(was), awt')
            ->addGroupBy('a')
            ->addOrderBy('wt.lastName')
            ->addOrderBy('wt.firstName')
            ->addOrderBy('wt.id')
            ->addOrderBy('awt.id', 'DESC')
            ->addOrderBy('pro.name');
    }
}
