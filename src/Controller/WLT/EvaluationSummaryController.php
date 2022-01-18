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

namespace App\Controller\WLT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\WLT\Agreement;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\SubjectRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WLT\ActivityRealizationRepository;
use App\Repository\WLT\WLTGroupRepository;
use App\Security\Edu\GroupVoter;
use App\Security\OrganizationVoter;
use App\Security\WLT\WLTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/dual/resultado/estudiante")
 */
class EvaluationSummaryController extends AbstractController
{
    /**
     * @Route("/listar/{academicYear}/{page}", name="work_linked_training_evaluation_summary_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        TeacherRepository $teacherRepository,
        WLTGroupRepository $wltGroupRepository,
        AcademicYearRepository $academicYearRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_VIEW_GRADE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->addSelect('COUNT(DISTINCT a)')
            ->addSelect('COUNT(ar)')
            ->addSelect('COUNT(ar.grade)')
            ->from(StudentEnrollment::class, 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join(Agreement::class, 'a', 'WITH', 'a.studentEnrollment = se')
            ->join('a.evaluatedActivityRealizations', 'ar')
            ->groupBy('se')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('g.name');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
        $isWorkTutor = $this->isGranted(WLTOrganizationVoter::WLT_WORK_TUTOR, $organization);

        if (false === $isManager && false === $isWltManager) {
            $person = $this->getUser()->getPerson();

            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor
            $teacher =
                $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

            if ($teacher) {
                $groups = $wltGroupRepository->findByAcademicYearAndPerson($academicYear, $teacher->getPerson());

                if ($groups->count() > 0) {
                    $queryBuilder
                        ->andWhere('g IN (:groups)')
                        ->setParameter('groups', $groups);
                }
                // si también es tutor laboral, mostrar los suyos aunque sean de otros grupos
                if ($isWorkTutor) {
                    $queryBuilder
                        ->orWhere('a.workTutor = :person')
                        ->setParameter('person', $person);
                }
            } else {
                // si solo es tutor laboral, necesita ser el tutor para verlo
                if ($isWorkTutor) {
                    $queryBuilder
                        ->andWhere('a.workTutor = :person')
                        ->setParameter('person', $person);
                }
            }
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
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.summary', [], 'wlt_agreement_activity_realization');

        return $this->render('wlt/evaluation/summary.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_agreement_activity_realization',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }
    /**
     * @Route("/{id}", name="work_linked_training_evaluation_summary_report",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function reportAction(
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        SubjectRepository $subjectRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        StudentEnrollment $studentEnrollment
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_VIEW_GRADE, $organization);

        $title = $translator->trans('title.report', [], 'wlt_agreement_activity_realization') .
            ' - ' . $studentEnrollment;

        $isGroupTutor = $this->isGranted(GroupVoter::MANAGE, $studentEnrollment->getGroup());
        $isWltManager = $this->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        $subjects = $subjectRepository->findByGroupAndPerson(
            $studentEnrollment->getGroup(),
            $isGroupTutor || $isWltManager ? null : $this->getUser()->getPerson()
        );

        $report = [];

        // precaching
        $activityRealizationRepository->findByStudentEnrollment($studentEnrollment);

        foreach ($subjects as $subject) {
            $item = [];
            $item[0] = $subject;
            $item[1] = $activityRealizationRepository->
                reportByStudentEnrollmentAndSubject($studentEnrollment, $subject);

            $report[] = $item;
        }

        $breadcrumb = [
            ['fixed' => (string) $studentEnrollment]
        ];

        return $this->render('wlt/evaluation/summary_report.html.twig', [
            'menu_path' => 'work_linked_training_evaluation_summary_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'report' => $report
        ]);
    }
}
