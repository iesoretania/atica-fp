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

namespace App\Controller\WPT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WPT\AgreementEnrollment;
use App\Entity\WPT\Shift;
use App\Entity\WPT\WorkTutorAnsweredSurvey;
use App\Form\Type\AnsweredSurveyType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\WPT\AgreementEnrollmentRepository;
use App\Repository\WPT\EducationalTutorAnsweredSurveyRepository;
use App\Repository\WPT\StudentAnsweredSurveyRepository;
use App\Repository\WPT\WorkTutorAnsweredSurveyRepository;
use App\Repository\WPT\WPTTeacherRepository;
use App\Security\WPT\AgreementEnrollmentVoter;
use App\Security\WPT\ShiftVoter;
use App\Security\WPT\WPTOrganizationVoter;
use App\Service\UserExtensionService;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/fct/encuesta")
 */
class SurveyController extends AbstractController
{
    /**
     * @param $queryBuilder
     * @param $pageSize
     * @param $page
     * @return Pagerfanta
     */
    private static function getPager($queryBuilder, $pageSize, $page): Pagerfanta
    {
        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($pageSize)
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }
        return $pager;
    }

    /**
     * @Route("/", name="workplace_training_survey", methods={"GET"})
     */
    public function indexAction(UserExtensionService $userExtensionService)
    {
        $this->denyAccessUnlessGranted(
            WPTOrganizationVoter::WPT_ACCESS,
            $userExtensionService->getCurrentOrganization()
        );
        return $this->render(
            'default/index.html.twig',
            [
                'menu' => true
            ]
        );
    }

    /**
     * @Route("/estudiante/{academicYear}/{page}", name="workplace_training_survey_student_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function studentListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        StudentAnsweredSurveyRepository $studentAnsweredSurveyRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS, $organization);

        $title = $translator->trans('title.survey.student.list', [], 'wpt_survey');

        /** @var Person $person */
        $person = $this->getUser();

        $q = $request->get('q');

        $queryBuilder = $studentAnsweredSurveyRepository->findByAcademicYearAndPersonFilterQueryBuilder(
            $q,
            $academicYear,
            $person
        );

        $pageSize = $this->getParameter('page.size');

        $pager = self::getPager($queryBuilder, $pageSize, $page);

        return $this->render('wpt/survey/student_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_survey',
            'route_name' => 'workplace_training_survey_student_form',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/estudiante/cumplimentar/{id}", name="workplace_training_survey_student_form", methods={"GET", "POST"})
     * @Security("is_granted('WPT_AGREEMENT_ENROLLMENT_VIEW_STUDENT_SURVEY', agreementEnrollment)")
     */
    public function studentFillAction(
        Request $request,
        TranslatorInterface $translator,
        StudentAnsweredSurveyRepository $studentAnsweredSurveyRepository,
        AgreementEnrollment $agreementEnrollment
    ) {
        $readOnly = !$this->isGranted(AgreementEnrollmentVoter::FILL_STUDENT_SURVEY, $agreementEnrollment);

        $academicYear = $agreementEnrollment
            ->getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();
        $agreement = $agreementEnrollment->getAgreement();
        $shift = $agreement->getShift();
        $studentAnsweredSurvey = $studentAnsweredSurveyRepository->findOneByShiftAndStudentEnrollment(
            $shift,
            $agreementEnrollment->getStudentEnrollment()
        );
        $survey = $shift
            ->getStudentSurvey();

        if ($survey) {
            if ($studentAnsweredSurvey === null) {
                $studentAnsweredSurvey = $studentAnsweredSurveyRepository->createNewAnsweredSurvey(
                    $survey,
                    $shift,
                    $agreementEnrollment->getStudentEnrollment()
                );
            }

            $studentSurvey = $studentAnsweredSurvey->getAnsweredSurvey();

            $form = $this->createForm(AnsweredSurveyType::class, $studentSurvey, [
                'disabled' => $readOnly
            ]);

            $form->handleRequest($request);

            $em = $this->getDoctrine()->getManager();

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $studentSurvey->setTimestamp(new \DateTime());
                    $em->flush();
                    $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_survey'));
                    return $this->redirectToRoute('workplace_training_survey_student_list', [
                        'academicYear' => $academicYear->getId()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', $translator->trans('message.error', [], 'wpt_survey'));
                }
            }
        } else {
            $form = $this->createForm(AnsweredSurveyType::class, null, [
                'disabled' => $readOnly
            ]);
        }

        $title = $translator->trans('title.fill', [], 'wpt_survey');

        $breadcrumb = [
            ['fixed' => $shift->__toString()],
            ['fixed' => $title]
        ];

        return $this->render('wpt/survey/form.html.twig', [
            'menu_path' => 'workplace_training_survey_student_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'shift' => $shift,
            'read_only' => $readOnly,
            'survey' => $survey,
            'person' => $agreementEnrollment->getStudentEnrollment()->getPerson(),
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/empresa/{academicYear}/{page}", name="workplace_training_survey_work_tutor_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function workTutorListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementEnrollmentRepository $agreementEnrollmentRepository,
        AcademicYearRepository $academicYearRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        if (!$this->isGranted(WPTOrganizationVoter::WPT_MANAGER, $organization) // jefe de departamento/administrador
            && !$this->isGranted(WPTOrganizationVoter::WPT_WORK_TUTOR, $organization)// tutor laboral
            && !$this->isGranted(WPTOrganizationVoter::WPT_EDUCATIONAL_TUTOR, $organization)// tutor docente
            && !$this->isGranted(WPTOrganizationVoter::WPT_GROUP_TUTOR, $organization)// tutor de grupo de FCT
        ) {
            throw $this->createAccessDeniedException();
        }

        $title = $translator->trans('title.survey.work_tutor.list', [], 'wpt_survey');

        /** @var Person $person */
        $person = $this->getUser();

        $q = $request->get('q');

        $queryBuilder = $agreementEnrollmentRepository->findByAcademicYearAndPersonFilterQueryBuilder(
            $q,
            $academicYear,
            $person
        );

        $queryBuilder
            ->leftJoin(
                WorkTutorAnsweredSurvey::class,
                'was',
                'WITH',
                '(was.workTutor = wt OR was.workTutor = awt) AND was.shift = shi'
            )
            ->addSelect('COUNT(was), awt')
            ->addGroupBy('ae')
            ->addOrderBy('wt.lastName')
            ->addOrderBy('wt.firstName')
            ->addOrderBy('wt.id')
            ->addOrderBy('awt.id', 'DESC')
            ->addOrderBy('shi.name');

        $pageSize = $this->getParameter('page.size');

        $pager = self::getPager($queryBuilder, $pageSize, $page);

        return $this->render('wpt/survey/work_tutor_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_survey',
            'route_name' => 'workplace_training_survey_student_form',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/empresa/cumplimentar/{id}/{workTutor}", name="workplace_training_survey_work_tutor_form")
     * @Security("is_granted('WPT_AGREEMENT_ENROLLMENT_VIEW_COMPANY_SURVEY', agreementEnrollment)")
     */
    public function workTutorFillAction(
        Request $request,
        TranslatorInterface $translator,
        WorkTutorAnsweredSurveyRepository $workTutorAnsweredSurveyRepository,
        AgreementEnrollment $agreementEnrollment,
        Person $workTutor
    ) {
        // sólo pueden rellenar la encuesta en nombre del responsable laboral titular o adicional
        if ($workTutor !== $agreementEnrollment->getWorkTutor()
            && $workTutor !== $agreementEnrollment->getAdditionalWorkTutor()) {
            throw $this->createAccessDeniedException();
        }

        $person = $this->getUser();

        $readOnly = !$this->isGranted(AgreementEnrollmentVoter::FILL_COMPANY_SURVEY, $agreementEnrollment);

        if (!$readOnly
            && !$this->isGranted(AgreementEnrollmentVoter::MANAGE, $agreementEnrollment)
            && $workTutor === $agreementEnrollment->getWorkTutor() && $person !== $agreementEnrollment->getWorkTutor()
        ) {
            $readOnly = true;
        }

        $agreement = $agreementEnrollment->getAgreement();
        $shift = $agreement->getShift();
        $academicYear = $agreementEnrollment
            ->getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();
        $workTutorAnsweredSurvey = $workTutorAnsweredSurveyRepository->findOneByShiftAndWorkTutor(
            $shift,
            $workTutor
        );

        $survey = $agreement
            ->getShift()
            ->getCompanySurvey();

        if ($survey) {
            if ($workTutorAnsweredSurvey === null) {
                $workTutorAnsweredSurvey = $workTutorAnsweredSurveyRepository->createNewAnsweredSurvey(
                    $survey,
                    $shift,
                    $workTutor
                );
            }

            $answeredSurvey = $workTutorAnsweredSurvey->getAnsweredSurvey();

            $form = $this->createForm(AnsweredSurveyType::class, $answeredSurvey, [
                'disabled' => $readOnly
            ]);

            $form->handleRequest($request);

            $em = $this->getDoctrine()->getManager();

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $answeredSurvey->setTimestamp(new \DateTime());
                    $em->flush();
                    $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_survey'));
                    return $this->redirectToRoute('workplace_training_survey_work_tutor_list', [
                        'academicYear' => $academicYear->getId()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', $translator->trans('message.error', [], 'wpt_survey'));
                }
            }
        } else {
            $form = $this->createForm(AnsweredSurveyType::class, null, [
                'disabled' => $readOnly
            ]);
        }

        $title = $translator->trans('title.fill', [], 'wpt_survey');

        $breadcrumb = [
            ['fixed' => $shift->__toString()],
            ['fixed' => $title]
        ];

        return $this->render('wpt/survey/form.html.twig', [
            'menu_path' => 'workplace_training_survey_work_tutor_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'shift' => $shift,
            'read_only' => $readOnly,
            'survey' => $survey,
            'person' => $workTutor,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/centro/{academicYear}/{page}", name="workplace_training_survey_educational_tutor_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function educationalTutorListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        WPTTeacherRepository $wptTeacherRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(
            [WPTOrganizationVoter::WPT_EDUCATIONAL_TUTOR, WPTOrganizationVoter::WPT_MANAGER],
            $organization
        );

        $title = $translator->trans('title.survey.educational_tutor.list', [], 'wpt_survey');

        /** @var Person $person */
        $person = $this->getUser();

        $q = $request->get('q');

        $teacher = $wptTeacherRepository->findOneByPersonAndAcademicYear($person, $academicYear);

        $queryBuilder = $wptTeacherRepository->findTeachersShiftDataByAcademicYearAndTeacherFilteredQueryBuilder(
            $q,
            $academicYear,
            $teacher
        );

        $pager = self::getPager($queryBuilder, $this->getParameter('page.size'), $page);

        return $this->render('wpt/survey/educational_tutor_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_survey',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/centro/cumplimentar/{id}/{teacher}",
     *     name="workplace_training_survey_educational_tutor_form",
     *     requirements={"shift" : "\d+", "id" : "\d+"}, methods={"GET", "POST"})
     */
    public function educationalTutorFillAction(
        Request $request,
        TranslatorInterface $translator,
        EducationalTutorAnsweredSurveyRepository $educationalTutorAnsweredSurveyRepository,
        AgreementEnrollmentRepository $agreementEnrollmentRepository,
        Shift $shift,
        Teacher $teacher
    ) {
        $em = $this->getDoctrine()->getManager();

        $this->denyAccessUnlessGranted(ShiftVoter::ACCESS_EDUCATIONAL_TUTOR_SURVEY, $shift);
        $readOnly = !$this->isGranted(ShiftVoter::FILL_EDUCATIONAL_TUTOR_SURVEY, $shift);

        $agreementCount = $agreementEnrollmentRepository->countTeacherAndShift(
            $teacher,
            $shift
        );

        // sólo pueden rellenar la encuesta de tutores docentes (titulares o adicionales)
        if ($agreementCount === 0) {
            throw $this->createAccessDeniedException();
        }

        $survey = $shift->getEducationalTutorSurvey();

        if ($survey) {
            $answeredSurvey =
                $educationalTutorAnsweredSurveyRepository->findOneByShiftAndTeacher(
                    $shift,
                    $teacher
                );

            if ($answeredSurvey === null) {
                $answeredSurvey = $educationalTutorAnsweredSurveyRepository->createNewAnsweredSurvey(
                    $survey,
                    $shift,
                    $teacher
                );
            }

            $teacherSurvey = $answeredSurvey->getAnsweredSurvey();

            $form = $this->createForm(AnsweredSurveyType::class, $teacherSurvey, [
                'disabled' => $readOnly
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $teacherSurvey->setTimestamp(new \DateTime());
                    $em->flush();
                    $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_survey'));
                    return $this->redirectToRoute('workplace_training_survey_educational_tutor_list', [
                        'academicYear' => $teacher->getAcademicYear()->getId()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', $translator->trans('message.error', [], 'wpt_survey'));
                }
            }
        } else {
            $form = $this->createForm(AnsweredSurveyType::class, null, [
                'disabled' => $readOnly
            ]);
        }

        $title = $translator->trans('title.fill', [], 'wpt_survey');

        $breadcrumb = [
            ['fixed' => $teacher],
            ['fixed' => $shift],
            ['fixed' => $title]
        ];
        $backUrl = $this->generateUrl('workplace_training_survey_educational_tutor_list', [
            'academicYear' => $teacher->getId()
        ]);
        return $this->render('wpt/survey/form.html.twig', [
            'menu_path' => 'workplace_training_survey_educational_tutor_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'shift' => $shift,
            'survey' => $shift->getEducationalTutorSurvey(),
            'person' => $teacher->getPerson(),
            'form' => $form->createView(),
            'back_url' => $backUrl
        ]);
    }
}
