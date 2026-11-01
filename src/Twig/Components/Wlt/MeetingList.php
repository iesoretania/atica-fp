<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WltModule\Meeting;
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

/** Reuniones de FP dual de un curso académico, con filtros, orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:MeetingList', template: 'components/Wlt/MeetingList.html.twig')]
final class MeetingList extends AbstractListComponent
{
    private TeacherRepository $teacherRepository;
    private WltGroupRepository $groupRepository;
    private ProjectRepository $projectRepository;

    #[Required]
    public function setRepositories(
        TeacherRepository $teacherRepository,
        WltGroupRepository $groupRepository,
        ProjectRepository $projectRepository,
    ): void {
        $this->teacherRepository = $teacherRepository;
        $this->groupRepository = $groupRepository;
        $this->projectRepository = $projectRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_ACCESS_MEETING, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    public function allowNew(): bool
    {
        return $this->security->isGranted(WltOrganizationVoter::WLT_CREATE_MEETING, $this->organization());
    }

    protected function fetchJoinCollection(): bool
    {
        return true;
    }

    public function filterDefinitions(): array
    {
        $choices = [];
        foreach ($this->projectRepository->findByOrganization($this->organization()) as $project) {
            $choices[(string) $project->getId()] = (string) $project;
        }

        return [
            'project' => [
                'type' => 'select',
                'label' => $this->translator->trans('header.project', [], 'wlt_meeting'),
                'choices' => $choices,
            ],
            'from' => ['type' => 'date', 'label' => $this->translator->trans('filters.date_from', [], 'general')],
            'to' => ['type' => 'date', 'label' => $this->translator->trans('filters.date_to', [], 'general')],
        ];
    }

    protected function sortableColumns(): array
    {
        return [
            'datetime' => 'm.dateTime',
            'created_by' => ['p.lastName', 'p.firstName'],
            'project' => 'pr.name',
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
            ->select('m', 'pr', 'cb', 'p')
            ->distinct()
            ->from(Meeting::class, 'm')
            ->join('m.project', 'pr')
            ->join('m.createdBy', 'cb')
            ->join('cb.person', 'p')
            ->leftJoin('m.teachers', 'te')
            ->leftJoin('te.person', 'tep')
            ->leftJoin('m.studentEnrollments', 'se')
            ->leftJoin('se.person', 'sep')
            ->addOrderBy('m.dateTime', 'DESC');

        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization);

        $groups = [];
        $projects = [];
        if (!$isWltManager && !$isManager) {
            // jefatura de departamento o tutoría de grupo: solo las reuniones del alumnado de sus grupos
            $groups = $this->groupRepository->findByAcademicYearAndGroupTutorOrDepartmentHeadPerson($this->academicYear, $person);
        } elseif ($isWltManager) {
            $projects = $this->projectRepository->findByManager($person);
        }

        if ($this->q !== '') {
            $queryBuilder
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('(pr.name IS NOT NULL AND pr.name LIKE :tq)')
                ->orWhere('(sep.firstName IS NOT NULL AND (sep.firstName LIKE :tq OR sep.lastName LIKE :tq))')
                ->orWhere('(tep.firstName IS NOT NULL AND (tep.firstName LIKE :tq OR tep.lastName LIKE :tq))')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        // se ven siempre las propias
        $teacher = $this->teacherRepository->findOneByAcademicYearAndPerson($this->academicYear, $person);

        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups) OR te = :teacher OR m.createdBy = :teacher')
                ->setParameter('groups', $groups)
                ->setParameter('teacher', $teacher);
        }

        if ($projects) {
            $queryBuilder
                ->andWhere('pr IN (:projects) OR te = :teacher OR m.createdBy = :teacher')
                ->setParameter('projects', $projects)
                ->setParameter('teacher', $teacher);
        }

        if (!$isWltManager && !$isManager && !$projects && !$groups) {
            $queryBuilder
                ->andWhere('m.createdBy = :teacher')
                ->setParameter('teacher', $teacher);
        }

        if (($project = $this->filter('project')) !== '') {
            $queryBuilder->andWhere('pr.id = :filter_project')->setParameter('filter_project', (int) $project);
        }
        if (($from = $this->filter('from')) !== '') {
            $queryBuilder->andWhere('m.dateTime >= :filter_from')->setParameter('filter_from', new \DateTime($from . ' 00:00:00'));
        }
        if (($to = $this->filter('to')) !== '') {
            $queryBuilder->andWhere('m.dateTime <= :filter_to')->setParameter('filter_to', new \DateTime($to . ' 23:59:59'));
        }

        return $queryBuilder
            ->andWhere('cb.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
