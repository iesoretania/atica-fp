<?php
/*
  Copyright (C) 2018: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace AppBundle\Controller\WLT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Role;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Repository\Edu\GroupRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\RoleRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/seguimiento")
 */
class TrackingController extends Controller
{
    /**
     * @Route("/estudiante/listar/{academicYear}/{page}", name="work_linked_training_tracking_list",
     *     requirements={"page" = "\d+"}, defaults={"academicYear" = null, "page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        RoleRepository $roleRepository,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
        Security $security,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(OrganizationVoter::ACCESS_WORK_LINKED_TRAINING, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->from(Agreement::class, 'a')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('c.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->orWhere('wt.firstName LIKE :tq')
                ->orWhere('wt.lastName LIKE :tq')
                ->orWhere('wt.uniqueIdentifier LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $person = $this->getUser()->getPerson();
        $isWltManager = $roleRepository->personHasRole(
            $organization,
            $person,
            Role::ROLE_WLT_MANAGER
        );
        $isDepartmentHead = $security->isGranted(OrganizationVoter::DEPARTMENT_HEAD, $organization);
        $isWorkTutor = $security->isGranted(OrganizationVoter::WLT_WORK_TUTOR, $organization);
        $isGroupTutor = $security->isGranted(OrganizationVoter::WLT_GROUP_TUTOR, $organization);
        $isTeacher = $security->isGranted(OrganizationVoter::WLT_TEACHER, $organization);

        if (false === $isManager && false === $isWltManager) {
            // si es tutor laboral, mostrar siempre
            if ($isWorkTutor) {
                $queryBuilder
                    ->orWhere('a.workTutor = :person')
                    ->setParameter('person', $person);
            }

            // si es estudiante, devolver sólo lo suyo
            $queryBuilder
                ->orWhere('se.person = :person')
                ->setParameter('person', $person);

            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor

            // vamos a buscar los grupos a los que tienen acceso
            $groups = new ArrayCollection();

            $teacher =
                $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

            if ($isDepartmentHead) {
                $newGroups = $groupRepository->findByAcademicYearAndWltHead($academicYear, $teacher);
                $this->appendGroups($groups, $newGroups);
            }
            if ($isGroupTutor) {
                $newGroups = $groupRepository->findByAcademicYearAndWltTutor($academicYear, $teacher);
                $this->appendGroups($groups, $newGroups);
            }
            if ($isTeacher) {
                $newGroups = $groupRepository->findByAcademicYearAndWltTeacher($academicYear, $teacher);
                $this->appendGroups($groups, $newGroups);
            }
            if ($groups->count() > 0) {
                $queryBuilder
                    ->orWhere('g IN (:groups)')
                    ->setParameter('groups', $groups);
            }
        }


        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $translator->trans('title.agreement.list', [], 'wlt_tracking');

        return $this->render('wlt/tracking/student_list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_tracking',
            'academic_year' => $academicYear
        ]);
    }

    private function appendGroups(ArrayCollection $groups, $newGroups)
    {
        foreach ($newGroups as $group) {
            if (false === $groups->contains($group)) {
                $groups->add($group);
            }
        }
    }
}
