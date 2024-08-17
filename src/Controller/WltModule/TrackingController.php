<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Controller\WltModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Person;
use App\Entity\WltModule\Agreement;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\WltModule\ProjectRepository;
use App\Repository\WltModule\GroupRepository;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/dual/seguimiento')]
class TrackingController extends AbstractController
{
    #[Route(path: '/acuerdo/listar/{academicYear}/{page}', name: 'work_linked_training_tracking_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                $request,
        UserExtensionService   $userExtensionService,
        TranslatorInterface    $translator,
        AcademicYearRepository $academicYearRepository,
        GroupRepository        $wltGroupRepository,
        ProjectRepository      $projectRepository,
        ManagerRegistry        $managerRegistry,
        AcademicYear           $academicYear = null,
        int                    $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->addSelect('pro')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
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

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('g.name LIKE :tq')
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
                ->setParameter('tq', '%'.$q.'%');
        }

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization);

        $groups = [];
        $projects = [];

        /** @var Person $person */
        $person = $this->getUser();
        if (!$isWltManager && !$isManager) {
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, docente o tutor de grupo -> ver los acuerdos de los
            // estudiantes de sus grupos
            $groups = $wltGroupRepository->findByAcademicYearAndGroupTutorOrTeacherOrDepartmentHeadPerson(
                $academicYear,
                $person
            );
        } elseif ($isWltManager) {
            $projects = $projectRepository->findByManager($person);
        }

        // ver siempre las propias
        if ($groups) {
            $queryBuilder
                ->andWhere(
                    'se.group IN (:groups) OR se.person = :person OR a.workTutor = :person OR et.person = :person OR ' .
                    'a.additionalWorkTutor = :person OR aet.person = :person'
                )
                ->setParameter('groups', $groups)
                ->setParameter('person', $person);
        }
        if ($projects) {
            $queryBuilder
                ->andWhere(
                    'pro IN (:projects) OR se.person = :person OR a.workTutor = :person OR et.person = :person OR ' .
                    'a.additionalWorkTutor = :person OR aet.person = :person'
                )
                ->setParameter('projects', $projects)
                ->setParameter('person', $person);
        }

        if (!$isWltManager && !$isManager && !$projects && !$groups) {
            $queryBuilder
                ->andWhere(
                    'se.person = :person OR a.workTutor = :person OR et.person = :person OR ' .
                    'a.additionalWorkTutor = :person OR aet.person = :person'
                )
                ->setParameter('person', $person);
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.agreement.list', [], 'wlt_tracking');

        return $this->render('wlt/tracking/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_tracking',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }
}
