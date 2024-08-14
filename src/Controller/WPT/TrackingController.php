<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Controller\WPT;

use App\Entity\Edu\AcademicYear;
use App\Entity\WPT\AgreementEnrollment;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WPT\WPTGroupRepository;
use App\Security\OrganizationVoter;
use App\Security\WPT\WPTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/fct/seguimiento')]
class TrackingController extends AbstractController
{
    /**
     * @param $person
     * @param $isManager
     * @param $q
     */
    private static function generateAgreementQueryBuilder(
        WPTGroupRepository $groupRepository,
        TeacherRepository $teacherRepository,
        AcademicYear $academicYear,
        QueryBuilder $queryBuilder,
        $person,
        $isManager,
        ?string $q
    ): QueryBuilder {
        $queryBuilder
            ->select('ae')
            ->addSelect('a')
            ->addSelect('shi')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->addSelect('wt')
            ->addSelect('et')
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
            ->leftJoin('ae.additionalWorkTutor', 'awt')
            ->join('ae.educationalTutor', 'et')
            ->leftJoin('ae.additionalEducationalTutor', 'aet')
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
            // puede ser jefe de departamento, tutor docente o tutor de grupo -> ver los acuerdos de los
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
                    'ae.workTutor = :person OR ae.educationalTutor = :teacher OR ' .
                    'ae.additionalWorkTutor = :person OR ae.additionalEducationalTutor = :teacher'
                )
                ->setParameter('groups', $groups)
                ->setParameter('person', $person)
                ->setParameter('teacher', $teacher);
        }

        if (false === $isManager && !$groups) {
            $queryBuilder
                ->andWhere('se.person = :person OR ae.workTutor = :person OR ae.educationalTutor = :teacher OR ' .
                           'ae.additionalWorkTutor = :person OR ae.additionalEducationalTutor = :teacher')
                ->setParameter('person', $person)
                ->setParameter('teacher', $teacher);
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        return $queryBuilder;
    }

    /**
     * @param $person
     * @param $isManager
     * @param $q
     * @param $page
     * @param $maxPerPage
     */
    public static function generateAgreementPaginator(
        WPTGroupRepository $groupRepository,
        TeacherRepository $teacherRepository,
        AcademicYear $academicYear,
        QueryBuilder $queryBuilder,
        $person,
        $isManager,
        ?string $q,
        int $page,
        int $maxPerPage
    ): Pagerfanta
    {
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
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }
        return $pager;
    }

    #[Route(path: '/acuerdo/listar/{academicYear}/{page}', name: 'workplace_training_tracking_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        WPTGroupRepository $groupRepository,
        TeacherRepository $teacherRepository,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS, $organization);

        $q = $request->get('q');
        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();
        $person = $this->getUser();
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
            'domain' => 'wpt_tracking',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/api/v2/acuerdo/listar', name: 'api_workplace_training_agreement_list', methods: ['GET'])]
    public function apiList(
        UserExtensionService $userExtensionService,
        WPTGroupRepository $groupRepository,
        TeacherRepository $teacherRepository,
        ManagerRegistry $managerRegistry
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();
        $person = $this->getUser();

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
