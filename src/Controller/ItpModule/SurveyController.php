<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Controller\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\Person;
use App\Entity\Survey;
use App\Form\Type\Edu\AnsweredSurveyType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\ItpModule\EducationalTutorAnsweredSurveyRepository;
use App\Repository\ItpModule\StudentAnsweredSurveyRepository;
use App\Repository\ItpModule\WorkTutorAnsweredSurveyRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\StudentProgramWorkcenterVoter;
use App\Security\OrganizationVoter;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/encuesta', name: 'in_company_training_phase_survey')]
class SurveyController extends AbstractController
{
    private function getPager(QueryBuilder $queryBuilder, int $pageSize, int $page): Pagerfanta
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

    #[Route(path: '/', name: '', methods: ['GET'])]
    public function index(UserExtensionService $userExtensionService): Response
    {
        $this->denyAccessUnlessGranted(
            ItpOrganizationVoter::ITP_ACCESS_SECTION,
            $userExtensionService->getCurrentOrganization()
        );
        return $this->render(
            'default/index.html.twig',
            [
                'menu' => true
            ]
        );
    }

    #[IsGranted(StudentProgramWorkcenterVoter::VIEW_STUDENT_SURVEY, subject: 'studentProgramWorkcenter')]
    #[Route(path: '/estudiante/cumplimentar/{id}', name: '_student_form', methods: ['GET', 'POST'])]
    public function studentFill(
        Request $request,
        TranslatorInterface $translator,
        StudentAnsweredSurveyRepository $studentAnsweredSurveyRepository,
        ManagerRegistry $managerRegistry,
        StudentProgramWorkcenter $studentProgramWorkcenter
    ): Response {
        $readOnly = !$this->isGranted(StudentProgramWorkcenterVoter::FILL_STUDENT_SURVEY, $studentProgramWorkcenter);

        $studentEnrollment = $studentProgramWorkcenter->getStudentProgram()->getStudentEnrollment();
        $academicYear = $studentEnrollment->getGroup()->getGrade()->getTraining()->getAcademicYear();
        $trainingProgram = $studentProgramWorkcenter->getStudentProgram()->getProgramGroup()->getProgramGrade()->getTrainingProgram();
        $studentAnsweredSurvey = $studentAnsweredSurveyRepository->findOneByTrainingProgramAndStudentEnrollment(
            $trainingProgram,
            $studentEnrollment
        );
        $survey = $trainingProgram
            ->getStudentSurvey();

        if ($survey instanceof Survey) {
            if ($studentAnsweredSurvey === null) {
                $studentAnsweredSurvey = $studentAnsweredSurveyRepository->createNewAnsweredSurvey(
                    $survey,
                    $studentProgramWorkcenter,
                    $studentEnrollment
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
                    $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_survey'));
                    return $this->redirectToRoute('in_company_training_phase_survey_student_list', [
                        'academicYear' => $academicYear->getId()
                    ]);
                } catch (\Exception) {
                    $this->addFlash('error', $translator->trans('message.error', [], 'wlt_survey'));
                }
            }
        } else {
            $form = $this->createForm(AnsweredSurveyType::class, null, [
                'disabled' => $readOnly
            ]);
        }

        $title = $translator->trans('title.fill', [], 'wlt_survey');

        $breadcrumb = [
            ['fixed' => $trainingProgram->__toString()],
            ['fixed' => $title]
        ];

        return $this->render('itp/survey/form.html.twig', [
            'menu_path' => 'in_company_training_phase_survey_student_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'project' => $trainingProgram,
            'academic_year' => $academicYear,
            'read_only' => $readOnly,
            'survey' => $survey,
            'person' => $studentEnrollment->getPerson(),
            'form' => $form->createView()
        ]);
    }

    #[IsGranted(StudentProgramWorkcenterVoter::VIEW_WORK_TUTOR_SURVEY, subject: 'studentProgramWorkcenter')]
    #[Route(path: '/empresa/cumplimentar/{id}/{workTutor}', name: '_work_tutor_form')]
    public function workTutorFill(
        Request                           $request,
        TranslatorInterface               $translator,
        WorkTutorAnsweredSurveyRepository $workTutorAnsweredSurveyRepository,
        StudentProgramWorkcenter          $studentProgramWorkcenter,
        ManagerRegistry                   $managerRegistry,
        Person                            $workTutor
    ): Response {
        // solo pueden rellenar la encuesta en nombre del responsable laboral titular o adicional
        if ($workTutor !== $studentProgramWorkcenter->getWorkTutor() && $workTutor !== $studentProgramWorkcenter->getAdditionalWorkTutor()) {
            throw $this->createAccessDeniedException();
        }

        $person = $this->getUser();

        $readOnly = !$this->isGranted(StudentProgramWorkcenterVoter::FILL_WORK_TUTOR_SURVEY, $studentProgramWorkcenter);

        if (!$readOnly
            && !$this->isGranted(StudentProgramWorkcenterVoter::MANAGE, $studentProgramWorkcenter)
            && $workTutor === $studentProgramWorkcenter->getWorkTutor() && $person !== $studentProgramWorkcenter->getWorkTutor()
        ) {
            $readOnly = true;
        }

        $trainingProgram = $studentProgramWorkcenter->getStudentProgram()->getProgramGroup()->getProgramGrade()->getTrainingProgram();
        $academicYear = $studentProgramWorkcenter->getStudentProgram()->getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();
        $workTutorAnsweredSurvey = $workTutorAnsweredSurveyRepository->findOneByTrainingProgramAcademicYearAndWorkTutor(
            $trainingProgram,
            $academicYear,
            $workTutor
        );

        $survey = $trainingProgram
            ->getCompanySurvey();

        if ($survey instanceof Survey) {
            if ($workTutorAnsweredSurvey === null) {
                $workTutorAnsweredSurvey = $workTutorAnsweredSurveyRepository->createNewAnsweredSurvey(
                    $survey,
                    $trainingProgram,
                    $academicYear,
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
                    $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_survey'));
                    return $this->redirectToRoute('in_company_training_phase_survey_work_tutor_list', [
                        'academicYear' => $academicYear->getId()
                    ]);
                } catch (\Exception) {
                    $this->addFlash('error', $translator->trans('message.error', [], 'wlt_survey'));
                }
            }
        } else {
            $form = $this->createForm(AnsweredSurveyType::class, null, [
                'disabled' => $readOnly
            ]);
        }

        $title = $translator->trans('title.fill', [], 'itp_survey');

        $breadcrumb = [
            ['fixed' => $trainingProgram->__toString()],
            ['fixed' => $title]
        ];

        return $this->render('itp/survey/form.html.twig', [
            'menu_path' => 'in_company_training_phase_survey_work_tutor_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'training_program' => $trainingProgram,
            'read_only' => $readOnly,
            'survey' => $survey,
            'person' => $workTutor,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/centro/cumplimentar/{id}/{teacher}', name: '_educational_tutor_form', requirements: ['studentProgramWorkcenter' => '\d+', 'id' => '\d+'], methods: ['GET', 'POST'])]
    public function educationalTutorFill(
        Request                                  $request,
        TranslatorInterface                      $translator,
        EducationalTutorAnsweredSurveyRepository $educationalTutorAnsweredSurveyRepository,
        ManagerRegistry                          $managerRegistry,
        StudentProgramWorkcenter                 $studentProgramWorkcenter,
        Teacher                                  $teacher
    ): Response {
        $em = $managerRegistry->getManager();

        $trainingProgram = $studentProgramWorkcenter->getStudentProgram()->getProgramGroup()->getProgramGrade()->getTrainingProgram();

        $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::VIEW_EDUCATIONAL_TUTOR_SURVEY, $studentProgramWorkcenter);
        $readOnly = !$this->isGranted(StudentProgramWorkcenterVoter::FILL_EDUCATIONAL_TUTOR_SURVEY, $studentProgramWorkcenter);

        $survey = $trainingProgram->getEducationalTutorSurvey();

        if ($survey instanceof Survey) {
            $answeredSurvey =
                $educationalTutorAnsweredSurveyRepository->findOneByTrainingProgramAndTeacher(
                    $trainingProgram,
                    $teacher
                );

            if ($answeredSurvey === null) {
                $answeredSurvey = $educationalTutorAnsweredSurveyRepository->createNewAnsweredSurvey(
                    $survey,
                    $trainingProgram,
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
                    $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_survey'));
                    return $this->redirectToRoute('in_company_training_phase_survey_educational_tutor_list', [
                        'academicYear' => $teacher->getAcademicYear()->getId()
                    ]);
                } catch (\Exception) {
                    $this->addFlash('error', $translator->trans('message.error', [], 'wlt_survey'));
                }
            }
        } else {
            $form = $this->createForm(AnsweredSurveyType::class, null, [
                'disabled' => $readOnly
            ]);
        }

        $title = $translator->trans('title.fill', [], 'itp_survey');

        $breadcrumb = [
            ['fixed' => $teacher],
            ['fixed' => $trainingProgram],
            ['fixed' => $title]
        ];
        $backUrl = $this->generateUrl('in_company_training_phase_survey_educational_tutor_list', [
            'academicYear' => $teacher->getId()
        ]);
        return $this->render('wlt/survey/form.html.twig', [
            'menu_path' => 'in_company_training_phase_survey_educational_tutor_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'project' => $trainingProgram,
            'survey' => $trainingProgram->getEducationalTutorSurvey(),
            'person' => $teacher->getPerson(),
            'form' => $form->createView(),
            'back_url' => $backUrl
        ]);
    }

    #[Route(path: '/estudiante/{academicYear}/{page}', name: '_student_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
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
        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_VIEW_EVALUATION, $organization);

        $title = $translator->trans('title.survey.student_program_workcenter.list', [], 'itp_survey');

        $person = $this->getUser();
        assert($person instanceof Person);

        $q = $request->get('q');

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $queryBuilder = $studentAnsweredSurveyRepository->createStatsByAcademicYearAndPersonFilterQueryBuilder(
            $academicYear,
            $isManager,
            $person,
            $q
        );

        $pageSize = $this->getParameter('page.size');

        $pager = $this->getPager($queryBuilder, $pageSize, $page);

        return $this->render('itp/survey/student_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_survey',
            'route_name' => 'in_company_training_phase_survey_student_form',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/empresa/{academicYear}/{page}', name: '_work_tutor_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function workTutorList(
        Request                           $request,
        UserExtensionService              $userExtensionService,
        TranslatorInterface               $translator,
        WorkTutorAnsweredSurveyRepository $workTutorAnsweredSurveyRepository,
        AcademicYearRepository            $academicYearRepository,
        int                               $page = 1,
        AcademicYear                      $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }
        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_WORK_TUTOR, $organization);

        $title = $translator->trans('title.survey.work_tutor.list', [], 'itp_survey');

        $person = $this->getUser();
        assert($person instanceof Person);

        $q = $request->get('q');

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $queryBuilder = $workTutorAnsweredSurveyRepository->createStatsByAcademicYearAndPersonFilterQueryBuilder(
            $academicYear,
            $isManager,
            $person,
            $q
        );

        $pageSize = $this->getParameter('page.size');

        $pager = $this->getPager($queryBuilder, $pageSize, $page);

        return $this->render('itp/survey/work_tutor_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_survey',
            'route_name' => 'in_company_training_phase_survey_work_tutor_form',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/centro/{academicYear}/{page}', name: '_educational_tutor_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function educationalTutorList(
        Request                                  $request,
        UserExtensionService                     $userExtensionService,
        TranslatorInterface                      $translator,
        AcademicYearRepository                   $academicYearRepository,
        EducationalTutorAnsweredSurveyRepository $educationalTutorAnsweredSurveyRepository,
        int                                      $page = 1,
        AcademicYear                             $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_EDUCATIONAL_TUTOR, $organization);

        $title = $translator->trans('title.survey.educational_tutor.list', [], 'itp_survey');

        $person = $this->getUser();
        assert($person instanceof Person);

        $q = $request->get('q');

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);
        $isItpManager = $this->isGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);

        $queryBuilder = $educationalTutorAnsweredSurveyRepository->createStatsByAcademicYearAndPersonFilterQueryBuilder(
            $academicYear,
            $isManager,
            $isItpManager,
            $person,
            $q
        );

        $pager = $this->getPager($queryBuilder, $this->getParameter('page.size'), $page);

        return $this->render('itp/survey/educational_tutor_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_survey',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }
}
