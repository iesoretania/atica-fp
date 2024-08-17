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

namespace App\Controller\WptModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\Survey;
use App\Entity\WptModule\AgreementEnrollment;
use App\Entity\WptModule\Shift;
use App\Entity\WptModule\WorkTutorAnsweredSurvey;
use App\Form\Type\AnsweredSurveyType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WptModule\AgreementEnrollmentRepository;
use App\Repository\WptModule\EducationalTutorAnsweredSurveyRepository;
use App\Repository\WptModule\StudentAnsweredSurveyRepository;
use App\Repository\WptModule\WorkTutorAnsweredSurveyRepository;
use App\Repository\WptModule\TeacherRepository as WptTeacherRepositoryAlias;
use App\Security\WptModule\AgreementEnrollmentVoter;
use App\Security\WptModule\ShiftVoter;
use App\Security\WptModule\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/fct/encuesta')]
class SurveyController extends AbstractController
{
    private function getPager($queryBuilder, int $pageSize, int $page): Pagerfanta
    {
        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($pageSize)
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }
        return $pager;
    }

    #[Route(path: '/', name: 'workplace_training_survey', methods: ['GET'])]
    public function index(UserExtensionService $userExtensionService): Response
    {
        $this->denyAccessUnlessGranted(
            OrganizationVoter::WPT_ACCESS,
            $userExtensionService->getCurrentOrganization()
        );
        return $this->render(
            'default/index.html.twig',
            [
                'menu' => true
            ]
        );
    }

    #[Route(path: '/estudiante/{academicYear}/{page}', name: 'workplace_training_survey_student_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function studentList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        StudentAnsweredSurveyRepository $studentAnsweredSurveyRepository,
        int $page = 1,
        AcademicYear $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }
        $this->denyAccessUnlessGranted(OrganizationVoter::WPT_ACCESS, $organization);

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

        $pager = $this->getPager($queryBuilder, $pageSize, $page);

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

    #[IsGranted(AgreementEnrollmentVoter::VIEW_STUDENT_SURVEY, subject: 'agreementEnrollment')]
    #[Route(path: '/estudiante/cumplimentar/{id}', name: 'workplace_training_survey_student_form', methods: ['GET', 'POST'])]
    public function studentFill(
        Request $request,
        TranslatorInterface $translator,
        StudentAnsweredSurveyRepository $studentAnsweredSurveyRepository,
        ManagerRegistry $managerRegistry,
        AgreementEnrollment $agreementEnrollment
    ): Response {
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

        if ($survey instanceof Survey) {
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

            $em = $managerRegistry->getManager();

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $studentSurvey->setTimestamp(new \DateTime());
                    $em->flush();
                    $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_survey'));
                    return $this->redirectToRoute('workplace_training_survey_student_list', [
                        'academicYear' => $academicYear->getId()
                    ]);
                } catch (\Exception) {
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

    #[Route(path: '/empresa/{academicYear}/{page}', name: 'workplace_training_survey_work_tutor_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function workTutorList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementEnrollmentRepository $agreementEnrollmentRepository,
        AcademicYearRepository $academicYearRepository,
        int $page = 1,
        AcademicYear $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        if (!$this->isGranted(OrganizationVoter::WPT_MANAGER, $organization) // jefe de departamento/administrador
            && !$this->isGranted(OrganizationVoter::WPT_WORK_TUTOR, $organization)// tutor laboral
            && !$this->isGranted(OrganizationVoter::WPT_EDUCATIONAL_TUTOR, $organization)// tutor docente
            && !$this->isGranted(OrganizationVoter::WPT_GROUP_TUTOR, $organization)// tutor de grupo de FCT
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

        $pager = $this->getPager($queryBuilder, $pageSize, $page);

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

    #[IsGranted(AgreementEnrollmentVoter::VIEW_COMPANY_SURVEY, subject: 'agreementEnrollment')]
    #[Route(path: '/empresa/cumplimentar/{id}/{workTutor}', name: 'workplace_training_survey_work_tutor_form')]
    public function workTutorFill(
        Request $request,
        TranslatorInterface $translator,
        WorkTutorAnsweredSurveyRepository $workTutorAnsweredSurveyRepository,
        AgreementEnrollment $agreementEnrollment,
        ManagerRegistry $managerRegistry,
        Person $workTutor
    ): Response {
        // solo pueden rellenar la encuesta en nombre del responsable laboral titular o adicional
        if ($workTutor !== $agreementEnrollment->getWorkTutor()
            && $workTutor !== $agreementEnrollment->getAdditionalWorkTutor()) {
            throw $this->createAccessDeniedException();
        }

        $person = $this->getUser();

        $readOnly = !$this->isGranted(AgreementEnrollmentVoter::FILL_COMPANY_SURVEY, $agreementEnrollment);

        if (!$readOnly
            && !$this->isGranted(AgreementEnrollmentVoter::MANAGE, $agreementEnrollment)
            && $workTutor === $agreementEnrollment->getWorkTutor() && $person !== $agreementEnrollment->getWorkTutor()
            && $person !== $agreementEnrollment->getEducationalTutor()->getPerson()
            && ($agreementEnrollment->getAdditionalEducationalTutor() instanceof Teacher
                && $person !== $agreementEnrollment->getAdditionalEducationalTutor()->getPerson()
            )
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

        if ($survey instanceof Survey) {
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

            $em = $managerRegistry->getManager();

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $answeredSurvey->setTimestamp(new \DateTime());
                    $em->flush();
                    $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_survey'));
                    return $this->redirectToRoute('workplace_training_survey_work_tutor_list', [
                        'academicYear' => $academicYear->getId()
                    ]);
                } catch (\Exception) {
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

    #[Route(path: '/centro/{academicYear}/{page}', name: 'workplace_training_survey_educational_tutor_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function educationalTutorList(
        Request                   $request,
        UserExtensionService      $userExtensionService,
        TranslatorInterface       $translator,
        AcademicYearRepository    $academicYearRepository,
        TeacherRepository         $teacherRepository,
        WptTeacherRepositoryAlias $wptTeacherRepository,
        int                       $page = 1,
        AcademicYear              $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(
            [OrganizationVoter::WPT_EDUCATIONAL_TUTOR, OrganizationVoter::WPT_MANAGER],
            $organization
        );

        $title = $translator->trans('title.survey.educational_tutor.list', [], 'wpt_survey');

        /** @var Person $person */
        $person = $this->getUser();

        $q = $request->get('q');

        $teacher = $teacherRepository->findOneByPersonAndAcademicYear($person, $academicYear);

        $queryBuilder = $wptTeacherRepository->findTeachersShiftDataByAcademicYearAndTeacherFilteredQueryBuilder(
            $q,
            $academicYear,
            $teacher
        );

        $pager = $this->getPager($queryBuilder, $this->getParameter('page.size'), $page);

        return $this->render('wpt/survey/educational_tutor_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_survey',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/centro/cumplimentar/{id}/{teacher}', name: 'workplace_training_survey_educational_tutor_form', requirements: ['shift' => '\d+', 'id' => '\d+'], methods: ['GET', 'POST'])]
    public function educationalTutorFill(
        Request $request,
        TranslatorInterface $translator,
        EducationalTutorAnsweredSurveyRepository $educationalTutorAnsweredSurveyRepository,
        AgreementEnrollmentRepository $agreementEnrollmentRepository,
        ManagerRegistry $managerRegistry,
        Shift $shift,
        Teacher $teacher
    ): Response {
        $em = $managerRegistry->getManager();

        $this->denyAccessUnlessGranted(ShiftVoter::ACCESS_EDUCATIONAL_TUTOR_SURVEY, $shift);
        $readOnly = !$this->isGranted(ShiftVoter::FILL_EDUCATIONAL_TUTOR_SURVEY, $shift);

        $agreementCount = $agreementEnrollmentRepository->countTeacherAndShift(
            $teacher,
            $shift
        );

        // solo pueden rellenar la encuesta de tutores docentes (titulares o adicionales)
        if ($agreementCount === 0) {
            throw $this->createAccessDeniedException();
        }

        $survey = $shift->getEducationalTutorSurvey();

        if ($survey instanceof Survey) {
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
                } catch (\Exception) {
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
