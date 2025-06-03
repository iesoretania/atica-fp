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
use App\Repository\ItpModule\StudentAnsweredSurveyRepository;
use App\Repository\ItpModule\TeacherRepository;
use App\Security\ItpModule\OrganizationVoter;
use App\Security\ItpModule\StudentProgramWorkcenterVoter;
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
            OrganizationVoter::ITP_ACCESS_SECTION,
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

    #[IsGranted(AgreementVoter::VIEW_COMPANY_SURVEY, subject: 'agreement')]
    #[Route(path: '/empresa/cumplimentar/{id}/{workTutor}', name: '_work_tutor_form')]
    public function workTutorFill(
        Request $request,
        TranslatorInterface $translator,
        WorkTutorAnsweredSurveyRepository $workTutorAnsweredSurveyRepository,
        Agreement $agreement,
        ManagerRegistry $managerRegistry,
        Person $workTutor
    ): Response {
        // solo pueden rellenar la encuesta en nombre del responsable laboral titular o adicional
        if ($workTutor !== $agreement->getWorkTutor() && $workTutor !== $agreement->getAdditionalWorkTutor()) {
            throw $this->createAccessDeniedException();
        }

        $person = $this->getUser();

        $readOnly = !$this->isGranted(AgreementVoter::FILL_COMPANY_SURVEY, $agreement);

        if (!$readOnly
            && !$this->isGranted(AgreementVoter::MANAGE, $agreement)
            && $workTutor === $agreement->getWorkTutor() && $person !== $agreement->getWorkTutor()
        ) {
            $readOnly = true;
        }

        $project = $agreement->getProject();
        $academicYear = $agreement->getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();
        $workTutorAnsweredSurvey = $workTutorAnsweredSurveyRepository->findOneByProjectAcademicYearAndWorkTutor(
            $project,
            $academicYear,
            $workTutor
        );

        $survey = $agreement
            ->getProject()
            ->getCompanySurvey();

        if ($survey instanceof Survey) {
            if ($workTutorAnsweredSurvey === null) {
                $workTutorAnsweredSurvey = $workTutorAnsweredSurveyRepository->createNewAnsweredSurvey(
                    $survey,
                    $project,
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
                    return $this->redirectToRoute('work_linked_training_survey_work_tutor_list', [
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
            ['fixed' => $project->__toString()],
            ['fixed' => $title]
        ];

        return $this->render('wlt/survey/form.html.twig', [
            'menu_path' => 'work_linked_training_survey_work_tutor_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'project' => $project,
            'read_only' => $readOnly,
            'survey' => $survey,
            'person' => $workTutor,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/centro/cumplimentar/{id}/{teacher}', name: '_educational_tutor_form', requirements: ['project' => '\d+', 'id' => '\d+'], methods: ['GET', 'POST'])]
    public function educationalTutorFill(
        Request $request,
        TranslatorInterface $translator,
        EducationalTutorAnsweredSurveyRepository $educationalTutorAnsweredSurveyRepository,
        AgreementRepository $agreementRepository,
        ManagerRegistry $managerRegistry,
        Project $project,
        Teacher $teacher
    ): Response {
        $em = $managerRegistry->getManager();

        $this->denyAccessUnlessGranted(ProjectVoter::ACCESS_EDUCATIONAL_TUTOR_SURVEY, $project);
        $readOnly = !$this->isGranted(ProjectVoter::FILL_EDUCATIONAL_TUTOR_SURVEY, $project);

        $agreementCount = $agreementRepository->countAcademicYearAndEducationalTutorPersonAndProject(
            $teacher->getAcademicYear(),
            $teacher->getPerson(),
            $project
        );

        // solo pueden rellenar la encuesta de tutores docentes (titulares o adicionales)
        if ($agreementCount === 0) {
            throw $this->createAccessDeniedException();
        }

        $survey = $project->getEducationalTutorSurvey();

        if ($survey instanceof Survey) {
            $answeredSurvey =
                $educationalTutorAnsweredSurveyRepository->findOneByProjectAndTeacher(
                    $project,
                    $teacher
                );

            if ($answeredSurvey === null) {
                $answeredSurvey = $educationalTutorAnsweredSurveyRepository->createNewAnsweredSurvey(
                    $survey,
                    $project,
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
                    return $this->redirectToRoute('work_linked_training_survey_educational_tutor_list', [
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

        $title = $translator->trans('title.fill', [], 'wlt_survey');

        $breadcrumb = [
            ['fixed' => $teacher],
            ['fixed' => $project],
            ['fixed' => $title]
        ];
        $backUrl = $this->generateUrl('work_linked_training_survey_educational_tutor_list', [
            'academicYear' => $teacher->getId()
        ]);
        return $this->render('wlt/survey/form.html.twig', [
            'menu_path' => 'work_linked_training_survey_educational_tutor_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'project' => $project,
            'survey' => $project->getEducationalTutorSurvey(),
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
        TeacherRepository $teacherRepository,
        StudentAnsweredSurveyRepository $studentAnsweredSurveyRepository,
        int $page = 1,
        AcademicYear $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }
        $this->denyAccessUnlessGranted(OrganizationVoter::ITP_VIEW_EVALUATION, $organization);

        $title = $translator->trans('title.survey.student_program_workcenter.list', [], 'itp_survey');

        $person = $this->getUser();
        assert($person instanceof Person);

        $q = $request->get('q');

        $isManager = $this->isGranted(OrganizationVoter::ITP_MANAGER, $organization);
        $teacher = $teacherRepository->findOneByPersonAndAcademicYear($person, $academicYear);

        $queryBuilder = $studentAnsweredSurveyRepository->findByAcademicYearAndPersonFilterQueryBuilder(
            $academicYear,
            $isManager,
            $person,
            $teacher,
            $q,
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
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        AcademicYearRepository $academicYearRepository,
        int $page = 1,
        AcademicYear $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }
        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS, $organization);

        $title = $translator->trans('title.survey.work_tutor.list', [], 'wlt_survey');

        /** @var Person $person */
        $person = $this->getUser();

        $q = $request->get('q');

        $queryBuilder = $agreementRepository->findByAcademicYearAndPersonFilterQueryBuilder(
            $q,
            $academicYear,
            $person
        );

        $queryBuilder
            ->leftJoin(
                WorkTutorAnsweredSurvey::class,
                'was',
                'WITH',
                '(was.workTutor = wt OR was.workTutor = awt) AND ' .
                'was.project = pro AND was.academicYear = :academic_year'
            )
            ->addSelect('COUNT(was), awt')
            ->addGroupBy('a')
            ->addOrderBy('wt.lastName')
            ->addOrderBy('wt.firstName')
            ->addOrderBy('wt.id')
            ->addOrderBy('awt.id', 'DESC')
            ->addOrderBy('pro.name');

        $pageSize = $this->getParameter('page.size');

        $pager = $this->getPager($queryBuilder, $pageSize, $page);

        return $this->render('wlt/survey/work_tutor_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_survey',
            'route_name' => 'work_linked_training_survey_student_form',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/centro/{academicYear}/{page}', name: '_educational_tutor_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function educationalTutorList(
        Request                $request,
        UserExtensionService   $userExtensionService,
        TranslatorInterface    $translator,
        AcademicYearRepository $academicYearRepository,
        TeacherRepository      $wltTeacherRepository,
        int                    $page = 1,
        AcademicYear           $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_EDUCATIONAL_TUTOR, $organization);

        $title = $translator->trans('title.survey.educational_tutor.list', [], 'wlt_survey');

        /** @var Person $person */
        $person = $this->getUser();

        $q = $request->get('q');

        $queryBuilder = $wltTeacherRepository->findTeachersDataByProjectGroupByProjectAndPersonFilteredQueryBuilder(
            $q,
            $academicYear,
            $person
        );

        $pager = $this->getPager($queryBuilder, $this->getParameter('page.size'), $page);

        return $this->render('wlt/survey/educational_tutor_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_survey',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }
}
