<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Person;
use App\Entity\WltModule\Agreement;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WltModule\GroupRepository as WltGroupRepository;
use App\Repository\WltModule\ProjectRepository;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Valoración de actividades de los convenios de FP dual de un curso académico, con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:EvaluationList', template: 'components/Wlt/EvaluationList.html.twig')]
final class EvaluationList extends AbstractListComponent
{
    private WltGroupRepository $wltGroupRepository;
    private ProjectRepository $projectRepository;
    private TeacherRepository $teacherRepository;

    #[Required]
    public function setRepositories(
        WltGroupRepository $wltGroupRepository,
        ProjectRepository $projectRepository,
        TeacherRepository $teacherRepository,
    ): void {
        $this->wltGroupRepository = $wltGroupRepository;
        $this->projectRepository = $projectRepository;
        $this->teacherRepository = $teacherRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_VIEW_EVALUATION, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function sortableColumns(): array
    {
        return [
            'student' => ['p.lastName', 'p.firstName'],
            'workcenter' => ['c.name', 'w.name'],
            'start' => 'a.startDate',
        ];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $organization = $this->organization();
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }

        $queryBuilder = $this->newQueryBuilder()
            ->select('a', 'w', 'c', 'se', 'p', 'g', 'pro')
            ->addSelect('COUNT(ear)')
            ->addSelect('COUNT(ear.grade)')
            ->from(Agreement::class, 'a')
            ->leftJoin('a.evaluatedActivityRealizations', 'ear')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->leftJoin('a.additionalWorkTutor', 'awt')
            ->join('a.educationalTutor', 'et')
            ->leftJoin('a.additionalEducationalTutor', 'aet')
            ->join('a.project', 'pro')
            ->groupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name');

        if ($this->q !== '') {
            $queryBuilder
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->orWhere('wt.firstName LIKE :tq')
                ->orWhere('wt.lastName LIKE :tq')
                ->orWhere('wt.uniqueIdentifier LIKE :tq')
                ->orWhere('awt.firstName LIKE :tq')
                ->orWhere('awt.lastName LIKE :tq')
                ->orWhere('awt.uniqueIdentifier LIKE :tq')
                ->orWhere('pro.name LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization);

        $groups = [];
        $projects = [];

        // acceso a sus estudiantes si es docente de algún grupo
        $teacher = $this->teacherRepository->findOneByAcademicYearAndPerson($this->academicYear, $person);

        if (!$isWltManager && !$isManager) {
            // jefatura de departamento, docente o tutoría de grupo: los convenios del alumnado de sus grupos
            $groups = $this->wltGroupRepository->findByAcademicYearAndGroupTutorOrDepartmentHeadPerson($this->academicYear, $person);

            if ($teacher !== null) {
                $groups = array_merge($groups->toArray(), $this->wltGroupRepository
                    ->findByAcademicYearAndPerson($this->academicYear, $teacher->getPerson())->toArray());
            }
        } elseif ($isWltManager) {
            $projects = $this->projectRepository->findByManager($person);
        }

        // se ven siempre los propios
        $own = 'se.person = :person OR a.workTutor = :person OR a.additionalWorkTutor = :person OR et.person = :person OR aet.person = :person';
        if ($groups) {
            $queryBuilder->andWhere('se.group IN (:groups) OR ' . $own)
                ->setParameter('groups', $groups)
                ->setParameter('person', $person);
        }
        if ($projects) {
            $queryBuilder->andWhere('pro IN (:projects) OR ' . $own)
                ->setParameter('projects', $projects)
                ->setParameter('person', $person);
        }
        if (!$isWltManager && !$isManager && !$projects && !$groups) {
            $queryBuilder->andWhere($own)->setParameter('person', $person);
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
