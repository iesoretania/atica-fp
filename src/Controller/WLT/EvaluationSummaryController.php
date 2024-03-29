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

namespace App\Controller\WLT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Person;
use App\Entity\WLT\Agreement;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\SubjectRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WLT\ActivityRealizationRepository;
use App\Repository\WLT\AgreementRepository;
use App\Repository\WLT\WLTGroupRepository;
use App\Security\Edu\GroupVoter;
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

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        /** @var Person $person */
        $person = $this->getUser();

        // Darle acceso si es profesor/a de algún grupo
        $teacher =
            $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        if ($teacher) {
            $groups = $wltGroupRepository->findByAcademicYearAndPerson($academicYear, $teacher->getPerson());
            if ($groups->count() > 0) {
                $queryBuilder
                    ->andWhere('g IN (:groups) OR et.person = :person OR aet.person = :person ' .
                    'OR a.workTutor = :person OR a.additionalWorkTutor = :person ' .
                    'OR pr.manager = :person OR h.person = :person')
                    ->setParameter('person', $person)
                    ->setParameter('groups', $groups);
            }
        } else {
            $isWltManager = $this->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
            if (!$isWltManager) {
                $queryBuilder
                    ->andWhere(
                        'et.person = :person OR aet.person = :person ' .
                        'OR a.workTutor = :person OR a.additionalWorkTutor = :person ' .
                        'OR pr.manager = :person OR h.person = :person'
                    )
                    ->setParameter('person', $person);
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
        AgreementRepository $agreementRepository,
        StudentEnrollment $studentEnrollment
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_VIEW_GRADE, $organization);

        $title = $translator->trans('title.report', [], 'wlt_agreement_activity_realization') .
            ' - ' . $studentEnrollment;

        $isGroupTutor = $this->isGranted(GroupVoter::MANAGE, $studentEnrollment->getGroup());
        $isWltManager = $this->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        /** @var Person $user */
        $user = $this->getUser();
        $subjects = $subjectRepository->findByGroupAndPerson(
            $studentEnrollment->getGroup(),
            $isGroupTutor || $isWltManager ? null : $user
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

        // Recopilar los comentarios de las empresas
        $agreements = $agreementRepository->findByStudentEnrollment($studentEnrollment);

        $reportRemarks = [];
        foreach ($agreements as $agreement) {
            if ($agreement->getWorkTutorRemarks()) {
                $reportRemarks[] = [$agreement->getWorkcenter(), $agreement->getWorkTutorRemarks()];
            }
        }

        $breadcrumb = [
            ['fixed' => (string) $studentEnrollment]
        ];

        return $this->render('wlt/evaluation/summary_report.html.twig', [
            'menu_path' => 'work_linked_training_evaluation_summary_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'report' => $report,
            'report_remarks' => $reportRemarks
        ]);
    }
}
