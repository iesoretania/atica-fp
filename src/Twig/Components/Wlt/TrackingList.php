<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Person;
use App\Entity\WltModule\Agreement;
use App\Repository\WltModule\GroupRepository;
use App\Repository\WltModule\ProjectRepository;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Seguimiento de convenios de FP dual de un curso académico, con filtro, orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:TrackingList', template: 'components/Wlt/TrackingList.html.twig')]
final class TrackingList extends AbstractListComponent
{
    private GroupRepository $wltGroupRepository;
    private ProjectRepository $projectRepository;

    #[Required]
    public function setRepositories(GroupRepository $wltGroupRepository, ProjectRepository $projectRepository): void
    {
        $this->wltGroupRepository = $wltGroupRepository;
        $this->projectRepository = $projectRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_ACCESS, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function sortableColumns(): array
    {
        return [
            'student' => ['p.lastName', 'p.firstName'],
            'project' => 'pro.name',
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
            ->select('a', 'pro', 'w', 'c', 'se', 'p', 'g')
            ->addSelect('SUM(wd.hours)')
            ->addSelect('SUM(CASE WHEN wd.absence = 0 THEN wd.locked * wd.hours ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence != 0 THEN 1 ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 2 THEN 1 ELSE 0 END)')
            ->from(Agreement::class, 'a')
            ->leftJoin('a.workDays', 'wd')
            ->join('a.project', 'pro')
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
            ->groupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name');

        if ($this->q !== '') {
            $queryBuilder
                ->orWhere('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
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
        if (!$isWltManager && !$isManager) {
            // jefatura de departamento, docente o tutoría de grupo: los convenios del alumnado de sus grupos
            $groups = $this->wltGroupRepository->findByAcademicYearAndGroupTutorOrTeacherOrDepartmentHeadPerson($this->academicYear, $person);
        } elseif ($isWltManager) {
            $projects = $this->projectRepository->findByManager($person);
        }

        // se ven siempre los propios
        $own = 'se.person = :person OR a.workTutor = :person OR et.person = :person OR a.additionalWorkTutor = :person OR aet.person = :person';
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
