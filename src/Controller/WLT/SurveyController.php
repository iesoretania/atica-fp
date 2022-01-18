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

use App\Entity\AnsweredSurvey;
use App\Entity\AnsweredSurveyQuestion;
use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\WLT\Agreement;
use App\Entity\WLT\EducationalTutorAnsweredSurvey;
use App\Entity\WLT\ManagerAnsweredSurvey;
use App\Entity\WLT\Project;
use App\Form\Type\AnsweredSurveyType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\WLT\EducationalTutorAnsweredSurveyRepository;
use App\Repository\WLT\ManagerAnsweredSurveyRepository;
use App\Repository\WLT\ProjectRepository;
use App\Repository\WLT\WLTGroupRepository;
use App\Security\OrganizationVoter;
use App\Security\WLT\AgreementVoter;
use App\Security\WLT\ProjectVoter;
use App\Security\WLT\WLTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/dual/encuesta")
 */
class SurveyController extends AbstractController
{
    /**
     * @Route("/estudiante/cumplimentar/{id}", name="work_linked_training_survey_student_form", methods={"GET", "POST"})
     * @Security("is_granted('WLT_AGREEMENT_VIEW_STUDENT_SURVEY', agreement)")
     */
    public function studentFillAction(Request $request, TranslatorInterface $translator, Agreement $agreement)
    {
        $readOnly = !$this->isGranted(AgreementVoter::FILL_STUDENT_SURVEY, $agreement);

        $academicYear = $agreement->getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();
        $studentSurvey = $agreement->getStudentSurvey();
        $survey = $agreement
            ->getProject()
            ->getStudentSurvey();

        if ($survey) {
            if ($studentSurvey === null) {
                $studentSurvey = new AnsweredSurvey();

                $studentSurvey->setSurvey($survey);

                $this->getDoctrine()->getManager()->persist($studentSurvey);

                foreach ($survey->getQuestions() as $question) {
                    $answeredQuestion = new AnsweredSurveyQuestion();
                    $answeredQuestion
                        ->setAnsweredSurvey($studentSurvey)
                        ->setSurveyQuestion($question);

                    $studentSurvey->getAnswers()->add($answeredQuestion);

                    $this->getDoctrine()->getManager()->persist($answeredQuestion);
                }

                $agreement->setStudentSurvey($studentSurvey);
            }

            $form = $this->createForm(AnsweredSurveyType::class, $studentSurvey, [
                'disabled' => $readOnly
            ]);

            $form->handleRequest($request);

            $em = $this->getDoctrine()->getManager();

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $studentSurvey->setTimestamp(new \DateTime());
                    $em->flush();
                    $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_survey'));
                    return $this->redirectToRoute('work_linked_training_survey_student_list', [
                        'academicYear' => $academicYear->getId()
                    ]);
                } catch (\Exception $e) {
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
            ['fixed' => (string) $agreement],
            ['fixed' => $title]
        ];

        return $this->render('wlt/survey/form.html.twig', [
            'menu_path' => 'work_linked_training_survey_student_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'read_only' => $readOnly,
            'survey' => $survey,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/empresa/cumplimentar/{id}", name="work_linked_training_survey_company_form", methods={"GET", "POST"})
     * @Security("is_granted('WLT_AGREEMENT_VIEW_COMPANY_SURVEY', agreement)")
     */
    public function companyFillAction(Request $request, TranslatorInterface $translator, Agreement $agreement)
    {
        $readOnly = !$this->isGranted(AgreementVoter::FILL_COMPANY_SURVEY, $agreement);

        $academicYear = $agreement->getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();
        $companySurvey = $agreement->getCompanySurvey();

        $survey = $agreement
            ->getProject()
            ->getCompanySurvey();

        if ($survey) {
            if ($companySurvey === null) {
                $companySurvey = new AnsweredSurvey();

                $companySurvey->setSurvey($survey);

                $this->getDoctrine()->getManager()->persist($companySurvey);

                foreach ($survey->getQuestions() as $question) {
                    $answeredQuestion = new AnsweredSurveyQuestion();
                    $answeredQuestion
                        ->setAnsweredSurvey($companySurvey)
                        ->setSurveyQuestion($question);

                    $companySurvey->getAnswers()->add($answeredQuestion);

                    $this->getDoctrine()->getManager()->persist($answeredQuestion);
                }

                $agreement->setCompanySurvey($companySurvey);
            }

            $form = $this->createForm(AnsweredSurveyType::class, $companySurvey, [
                'disabled' => $readOnly
            ]);

            $form->handleRequest($request);

            $em = $this->getDoctrine()->getManager();

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $companySurvey->setTimestamp(new \DateTime());
                    $em->flush();
                    $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_survey'));
                    return $this->redirectToRoute('work_linked_training_survey_company_list', [
                        'academicYear' => $academicYear->getId()
                    ]);
                } catch (\Exception $e) {
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
            ['fixed' => (string) $agreement],
            ['fixed' => $title]
        ];

        return $this->render('wlt/survey/form.html.twig', [
            'menu_path' => 'work_linked_training_survey_company_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'read_only' => $readOnly,
            'survey' => $survey,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/", name="work_linked_training_survey", methods={"GET"})
     */
    public function indexAction(UserExtensionService $userExtensionService)
    {
        $this->denyAccessUnlessGranted(
            WLTOrganizationVoter::WLT_ACCESS,
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
     * @Route("/estudiante/{academicYear}/{page}", name="work_linked_training_survey_student_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function studentListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ProjectRepository $projectRepository,
        AcademicYearRepository $academicYearRepository,
        WLTGroupRepository $wltGroupRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        return $this->agreementList(
            $request,
            $userExtensionService,
            $translator,
            $projectRepository,
            $academicYearRepository,
            $wltGroupRepository,
            $page,
            'work_linked_training_survey_student_form',
            'wlt/survey/student_list.html.twig',
            $academicYear
        );
    }

    /**
     * @Route("/empresa/{academicYear}/{page}", name="work_linked_training_survey_company_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function companyListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ProjectRepository $projectRepository,
        AcademicYearRepository $academicYearRepository,
        WLTGroupRepository $wltGroupRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        return $this->agreementList(
            $request,
            $userExtensionService,
            $translator,
            $projectRepository,
            $academicYearRepository,
            $wltGroupRepository,
            $page,
            'work_linked_training_survey_company_form',
            'wlt/survey/company_list.html.twig',
            $academicYear
        );
    }

    private function agreementList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ProjectRepository $projectRepository,
        AcademicYearRepository $academicYearRepository,
        WLTGroupRepository $wltGroupRepository,
        $page,
        $routeName,
        $template,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS, $organization);

        $title = $translator->trans('title.survey.agreement.list', [], 'wlt_survey');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->addSelect('pro')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
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
                ->orWhere('pro.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        $groups = [];
        $projects = [];

        $person = $this->getUser()->getPerson();
        if (false === $isWltManager && false === $isManager) {
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento o tutor de grupo  -> ver los acuerdos de los
            // estudiantes de sus grupos
            $groups = $wltGroupRepository
                ->findByAcademicYearAndGroupTutorOrDepartmentHeadPerson($academicYear, $person);
        } elseif ($isWltManager) {
            $projects = $projectRepository->findByManager($person);
        }

        // ver siempre las propias
        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups) OR se.person = :person OR wt = :person')
                ->setParameter('groups', $groups)
                ->setParameter('person', $person);
        }
        if ($projects) {
            $queryBuilder
                ->andWhere('pro IN (:projects) OR se.person = :person OR wt = :person')
                ->setParameter('projects', $projects)
                ->setParameter('person', $person);
        }

        if (false === $isWltManager && false === $isManager && !$projects && !$groups) {
            $queryBuilder
                ->andWhere('se.person = :person OR wt = :person')
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
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        return $this->render($template, [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_survey',
            'route_name' => $routeName,
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/centro/cumplimentar/{project}/{id}",
     *     name="work_linked_training_survey_educational_tutor_form",
     *     requirements={"project" : "\d+", "id" : "\d+"}, methods={"GET", "POST"})
     */
    public function educationalTutorFillAction(
        Request $request,
        TranslatorInterface $translator,
        EducationalTutorAnsweredSurveyRepository $educationalTutorAnsweredSurveyRepository,
        ProjectRepository $projectRepository,
        Project $project,
        Teacher $teacher
    ) {
        $em = $this->getDoctrine()->getManager();

        $this->denyAccessUnlessGranted(ProjectVoter::ACCESS_EDUCATIONAL_TUTOR_SURVEY, $project);
        $readOnly = !$this->isGranted(ProjectVoter::FILL_EDUCATIONAL_TUTOR_SURVEY, $project);

        $projects = $projectRepository->findByAcademicYear($teacher->getAcademicYear());

        // comprobar que el curso académico pertenece al proyecto
        if (!in_array($project, $projects, true)) {
            throw $this->createAccessDeniedException();
        }

        if ($project->getEducationalTutorSurvey()) {
            $answeredSurvey =
                $educationalTutorAnsweredSurveyRepository->findOneByProjectAndTeacher(
                    $project,
                    $teacher
                );

            if ($answeredSurvey === null) {
                $teacherSurvey = new AnsweredSurvey();
                $em->persist($teacherSurvey);

                $survey = $project->getEducationalTutorSurvey();

                $teacherSurvey->setSurvey($survey);

                $this->getDoctrine()->getManager()->persist($teacherSurvey);

                foreach ($survey->getQuestions() as $question) {
                    $answeredQuestion = new AnsweredSurveyQuestion();
                    $answeredQuestion
                        ->setAnsweredSurvey($teacherSurvey)
                        ->setSurveyQuestion($question);

                    $teacherSurvey->getAnswers()->add($answeredQuestion);

                    $this->getDoctrine()->getManager()->persist($answeredQuestion);
                }

                $managerAnsweredSurvey = new EducationalTutorAnsweredSurvey();
                $managerAnsweredSurvey
                    ->setProject($project)
                    ->setTeacher($teacher)
                    ->setAnsweredSurvey($teacherSurvey);

                $em->persist($managerAnsweredSurvey);
            } else {
                $teacherSurvey = $answeredSurvey->getAnsweredSurvey();
            }

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
                } catch (\Exception $e) {
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
            ['fixed' => $teacher->getAcademicYear()->getDescription()],
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
            'survey' => $project->getEducationalTutorSurvey(),
            'form' => $form->createView(),
            'back_url' => $backUrl
        ]);
    }

    /**
     * @Route("/centro/{academicYear}/{page}", name="work_linked_training_survey_educational_tutor_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function educationalTutorListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ProjectRepository $projectRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_EDUCATIONAL_TUTOR, $organization);

        $title = $translator->trans('title.survey.organization.list', [], 'wlt_survey');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->addSelect('pro.id AS projectId')
            ->addSelect('pro.name AS projectName')
            ->addSelect('ay.id AS academicYearId')
            ->addSelect('ay.description AS academicYearDescription')
            ->addSelect('COUNT(etas)')
            ->from(Teacher::class, 't')
            ->join(Agreement::class, 'a', 'WITH', 'a.educationalTutor = t')
            ->join(Project::class, 'pro', 'WITH', 'a.project = pro')
            ->join('t.person', 'p')
            ->join('a.studentEnrollment', 'se')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.academicYear', 'ay')
            ->leftJoin(
                EducationalTutorAnsweredSurvey::class,
                'etas',
                'WITH',
                'etas.teacher = t AND etas.project = pro'
            )
            ->addGroupBy('t, pro')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('pro.name');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('pro.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        $person = $this->getUser()->getPerson();
        if (!$isManager && $isWltManager) {
            $projects = $projectRepository->findByManager($person);
            $queryBuilder
                ->andWhere('pro IN (:projects) OR p = :person')
                ->setParameter('projects', $projects)
                ->setParameter('person', $person);
        }

        if (!$isWltManager && !$isManager) {
            $queryBuilder
                ->andWhere('p = :person')
                ->setParameter('person', $person);
        }

        $queryBuilder
            ->andWhere('tr.academicYear = :academic_year')
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
