<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Edu\StudentEnrollment;
use App\Entity\Person;
use App\Entity\WltModule\Agreement;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WltModule\GroupRepository;
use App\Security\WltModule\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Resultados de aprendizaje del alumnado de FP dual de un curso académico, con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:EvaluationSummaryList', template: 'components/Wlt/EvaluationSummaryList.html.twig')]
final class EvaluationSummaryList extends AbstractListComponent
{
    private TeacherRepository $teacherRepository;
    private GroupRepository $wltGroupRepository;

    #[Required]
    public function setRepositories(TeacherRepository $teacherRepository, GroupRepository $wltGroupRepository): void
    {
        $this->teacherRepository = $teacherRepository;
        $this->wltGroupRepository = $wltGroupRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(OrganizationVoter::WLT_VIEW_GRADE, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function sortableColumns(): array
    {
        return ['student' => ['p.lastName', 'p.firstName'], 'group' => 'g.name'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }

        $queryBuilder = $this->newQueryBuilder()
            ->select('se', 'p', 'g')
            ->addSelect('COUNT(DISTINCT a)')
            ->addSelect('COUNT(ar)')
            ->addSelect('COUNT(ar.grade)')
            ->from(StudentEnrollment::class, 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->leftJoin('t.department', 'd')
            ->leftJoin('d.head', 'h')
            ->join(Agreement::class, 'a', 'WITH', 'a.studentEnrollment = se')
            ->join('a.evaluatedActivityRealizations', 'ar')
            ->join('a.educationalTutor', 'et')
            ->leftJoin('a.additionalEducationalTutor', 'aet')
            ->join('a.project', 'pr')
            ->groupBy('se')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('g.name');

        if ($this->q !== '') {
            $queryBuilder
                ->orWhere('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        // acceso si es docente de algún grupo
        $teacher = $this->teacherRepository->findOneByAcademicYearAndPerson($this->academicYear, $person);

        if ($teacher) {
            $groups = $this->wltGroupRepository->findByAcademicYearAndPerson($this->academicYear, $teacher->getPerson());
            if ($groups->count() > 0) {
                $queryBuilder
                    ->andWhere('g IN (:groups) OR et.person = :person OR aet.person = :person ' .
                        'OR a.workTutor = :person OR a.additionalWorkTutor = :person ' .
                        'OR pr.manager = :person OR h.person = :person')
                    ->setParameter('person', $person)
                    ->setParameter('groups', $groups);
            }
        } elseif (!$this->security->isGranted(OrganizationVoter::WLT_MANAGER, $this->organization())) {
            $queryBuilder
                ->andWhere(
                    'et.person = :person OR aet.person = :person ' .
                    'OR a.workTutor = :person OR a.additionalWorkTutor = :person ' .
                    'OR pr.manager = :person OR h.person = :person'
                )
                ->setParameter('person', $person);
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
