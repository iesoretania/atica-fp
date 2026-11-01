<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Person;
use App\Entity\WltModule\Contact;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\WltModule\GroupRepository as WltGroupRepository;
use App\Repository\WltModule\ProjectRepository;
use App\Repository\WltModule\TeacherRepository as WltTeacherRepository;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Contactos de FP dual de un curso académico, con filtros (forma y fechas), orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:ContactList', template: 'components/Wlt/ContactList.html.twig')]
final class ContactList extends AbstractListComponent
{
    private WltTeacherRepository $wltTeacherRepository;
    private WltGroupRepository $wltGroupRepository;
    private ProjectRepository $projectRepository;
    private ContactMethodRepository $contactMethodRepository;

    #[Required]
    public function setRepositories(
        WltTeacherRepository $wltTeacherRepository,
        WltGroupRepository $wltGroupRepository,
        ProjectRepository $projectRepository,
        ContactMethodRepository $contactMethodRepository,
    ): void {
        $this->wltTeacherRepository = $wltTeacherRepository;
        $this->wltGroupRepository = $wltGroupRepository;
        $this->projectRepository = $projectRepository;
        $this->contactMethodRepository = $contactMethodRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_ACCESS_VISIT, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    public function allowNew(): bool
    {
        return $this->security->isGranted(WltOrganizationVoter::WLT_CREATE_VISIT, $this->organization());
    }

    protected function fetchJoinCollection(): bool
    {
        return true;
    }

    public function filterDefinitions(): array
    {
        $methods = ['0' => $this->translator->trans('form.on-site', [], 'wlt_contact')];
        foreach ($this->contactMethodRepository->findEnabledByAcademicYear($this->academicYear) as $method) {
            $methods[(string) $method->getId()] = (string) $method->getDescription();
        }

        return [
            'method' => ['type' => 'select', 'label' => $this->translator->trans('header.method', [], 'wlt_contact'), 'choices' => $methods],
            'from' => ['type' => 'date', 'label' => $this->translator->trans('filters.date_from', [], 'general')],
            'to' => ['type' => 'date', 'label' => $this->translator->trans('filters.date_to', [], 'general')],
        ];
    }

    protected function sortableColumns(): array
    {
        return ['datetime' => 'v.dateTime', 'teacher' => ['p.lastName', 'p.firstName'], 'method' => 'mt.description', 'workcenter' => ['c.name', 'w.name']];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $organization = $this->organization();
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }

        $queryBuilder = $this->newQueryBuilder()
            ->select('v', 't', 'p', 'w', 'c', 'pr', 'se', 'sep', 'seg', 'mt')
            ->distinct()
            ->from(Contact::class, 'v')
            ->join('v.teacher', 't')
            ->join('t.person', 'p')
            ->join('v.workcenter', 'w')
            ->join('w.company', 'c')
            ->leftJoin('v.method', 'mt')
            ->leftJoin('v.projects', 'pr')
            ->leftJoin('v.studentEnrollments', 'se')
            ->leftJoin('se.person', 'sep')
            ->leftJoin('se.group', 'seg')
            ->addOrderBy('v.dateTime', 'DESC');

        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $this->security->isGranted(WltOrganizationVoter::WLT_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $projects = [];
        if (!$isWltManager && !$isManager && !$isDepartmentHead) {
            // tutoría de grupo: solo los contactos del alumnado de sus grupos
            $groups = $this->wltGroupRepository->findByAcademicYearAndGroupTutorOrDepartmentHeadPerson($this->academicYear, $person);
        } elseif ($isWltManager) {
            $projects = $this->projectRepository->findByManager($person);
        } elseif ($isDepartmentHead) {
            $projects = $this->projectRepository->findByDepartmentHeadPerson($person);
        }

        if ($this->q !== '') {
            $queryBuilder
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('(pr.name IS NOT NULL AND pr.name LIKE :tq)')
                ->orWhere('(seg.name IS NOT NULL AND seg.name LIKE :tq)')
                ->orWhere('(sep.firstName IS NOT NULL AND (sep.firstName LIKE :tq OR sep.lastName LIKE :tq))')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        // se ven siempre los propios
        $teacher = $this->wltTeacherRepository->findOneByAcademicYearAndPerson($this->academicYear, $person);

        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups) OR v.teacher = :teacher')
                ->setParameter('groups', $groups)
                ->setParameter('teacher', $teacher);
        }

        if ($projects) {
            $queryBuilder
                ->andWhere('pr IN (:projects) OR v.teacher = :teacher')
                ->setParameter('projects', $projects)
                ->setParameter('teacher', $teacher);
        }

        $method = $this->filter('method');
        if ($method === '0') {
            $queryBuilder->andWhere('v.method IS NULL');
        } elseif ($method !== '') {
            $queryBuilder->andWhere('v.method = :filter_method')->setParameter('filter_method', (int) $method);
        }
        if (($from = $this->filter('from')) !== '') {
            $queryBuilder->andWhere('v.dateTime >= :filter_from')->setParameter('filter_from', new \DateTime($from . ' 00:00:00'));
        }
        if (($to = $this->filter('to')) !== '') {
            $queryBuilder->andWhere('v.dateTime < :filter_to')->setParameter('filter_to', (new \DateTime($to . ' 00:00:00'))->modify('+1 day'));
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
