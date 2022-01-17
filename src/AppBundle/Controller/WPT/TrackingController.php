<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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

namespace AppBundle\Controller\WPT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\WPT\AgreementEnrollment;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\WPT\WPTGroupRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WPT\WPTOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/fct/seguimiento")
 */
class TrackingController extends Controller
{
    /**
     * @param WPTGroupRepository $groupRepository
     * @param TeacherRepository $teacherRepository
     * @param AcademicYear $academicYear
     * @param QueryBuilder $queryBuilder
     * @param $person
     * @param $isManager
     * @param $q
     * @return QueryBuilder
     */
    private static function generateAgreementQueryBuilder(
        WPTGroupRepository $groupRepository,
        TeacherRepository $teacherRepository,
        AcademicYear $academicYear,
        QueryBuilder $queryBuilder,
        $person,
        $isManager,
        $q
    ) {
        $queryBuilder
            ->select('ae')
            ->addSelect('a')
            ->addSelect('shi')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->addSelect('SUM(wd.hours)')
            ->addSelect('SUM(CASE WHEN twd.absence = 0 THEN twd.locked * wd.hours ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN twd.absence != 0 THEN 1 ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN twd.absence = 2 THEN 1 ELSE 0 END)')
            ->from(AgreementEnrollment::class, 'ae')
            ->join('ae.agreement', 'a')
            ->join('a.workDays', 'wd')
            ->leftJoin('ae.trackedWorkDays', 'twd', 'WITH', 'twd.workDay = wd')
            ->join('a.shift', 'shi')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('ae.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('ae.workTutor', 'wt')
            ->join('ae.educationalTutor', 'et')
            ->join('et.person', 'etp')
            ->groupBy('ae')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name')
            ->addOrderBy('w.name');

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
                ->orWhere('etp.firstName LIKE :tq')
                ->orWhere('etp.lastName LIKE :tq')
                ->orWhere('wt.uniqueIdentifier LIKE :tq')
                ->orWhere('shi.name LIKE :tq')
                ->setParameter('tq', '%' . $q . '%');
        }

        $groups = [];

        $teacher = $teacherRepository->findOneByPersonAndAcademicYear($person, $academicYear);

        if (false === $isManager) {
            // no es administrador ni directivo:
            // puede ser jefe de departamento, tutor docente o tutor de grupo  -> ver los acuerdos de los
            // estudiantes de sus grupos
            $groups = $groupRepository->findByAcademicYearAndWPTGroupTutorOrDepartmentHeadPerson(
                $academicYear,
                $person
            );
        }

        // ver siempre las propias
        if ($groups) {
            $queryBuilder
                ->andWhere(
                    'se.group IN (:groups) OR se.person = :person OR ' .
                    'ae.workTutor = :person OR ae.educationalTutor = :teacher'
                )
                ->setParameter('groups', $groups)
                ->setParameter('person', $person)
                ->setParameter('teacher', $teacher);
        }

        if (false === $isManager && !$groups) {
            $queryBuilder
                ->andWhere('se.person = :person OR ae.workTutor = :person OR ae.educationalTutor = :teacher')
                ->setParameter('person', $person)
                ->setParameter('teacher', $teacher);
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        return $queryBuilder;
    }

    /**
     * @param WPTGroupRepository $groupRepository
     * @param TeacherRepository $teacherRepository
     * @param AcademicYear $academicYear
     * @param QueryBuilder $queryBuilder
     * @param $person
     * @param $isManager
     * @param $q
     * @param $page
     * @param $maxPerPage
     * @return Pagerfanta
     */
    public static function generateAgreementPaginator(
        WPTGroupRepository $groupRepository,
        TeacherRepository $teacherRepository,
        AcademicYear $academicYear,
        QueryBuilder $queryBuilder,
        $person,
        $isManager,
        $q,
        $page,
        $maxPerPage
    ) {
        $queryBuilder = self::generateAgreementQueryBuilder(
            $groupRepository,
            $teacherRepository,
            $academicYear,
            $queryBuilder,
            $person,
            $isManager,
            $q
        );

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($maxPerPage)
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }
        return $pager;
    }

    /**
     * @Route("/acuerdo/listar/{academicYear}/{page}", name="workplace_training_tracking_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        WPTGroupRepository $groupRepository,
        TeacherRepository $teacherRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if ($academicYear === null) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS, $organization);

        $q = $request->get('q');
        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $person = $this->getUser()->getPerson();
        $maxPerPage = $this->getParameter('page.size');

        $pager = self::generateAgreementPaginator(
            $groupRepository,
            $teacherRepository,
            $academicYear,
            $queryBuilder,
            $person,
            $isManager,
            $q,
            $page,
            $maxPerPage
        );

        $title = $translator->trans('title.agreement.list', [], 'wpt_tracking');

        return $this->render('wpt/tracking/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_tracking',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/api/v2/acuerdo/listar", name="api_workplace_training_agreement_list",
     *     methods={"GET"})
     */
    public function apiListAction(
        UserExtensionService $userExtensionService,
        WPTGroupRepository $groupRepository,
        TeacherRepository $teacherRepository
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $person = $this->getUser()->getPerson();

        $queryBuilder = self::generateAgreementQueryBuilder(
            $groupRepository,
            $teacherRepository,
            $academicYear,
            $queryBuilder,
            $person,
            $isManager,
            ''
        );

        $agreements = $queryBuilder->getQuery()->getArrayResult();
        $agreements2 = [
            'agreements' => []
        ];
        foreach ($agreements as $agreement) {
            $agreements2['agreements'][] = [
                'agreement' => $agreement[0],
                'horas_totales' => $agreement[1],
                'horas_bloqueadas' => $agreement[2],
                'jornadas_sin_asistir' => $agreement[3],
                'faltas_justificadas' => $agreement[4]
            ];
        }
        return new JsonResponse($agreements2);
    }
}
