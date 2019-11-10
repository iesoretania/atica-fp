<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Controller\WLT;

use AppBundle\Entity\AnsweredSurvey;
use AppBundle\Entity\AnsweredSurveyQuestion;
use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Form\Type\AnsweredSurveyType;
use AppBundle\Repository\Edu\GroupRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WLT\AgreementVoter;
use AppBundle\Security\WLT\WLTOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/encuesta")
 */
class SurveyController extends Controller
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
        if ($studentSurvey === null) {
            $studentSurvey = new AnsweredSurvey();
            $survey = $agreement
                ->getProject()
                ->getStudentSurvey();

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
                    'academicYear' => $academicYear
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_survey'));
            }
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
            'survey' => $studentSurvey->getSurvey(),
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
        if ($companySurvey === null) {
            $companySurvey = new AnsweredSurvey();
            $survey = $agreement
                ->getProject()
                ->getCompanySurvey();

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
                    'academicYear' => $academicYear
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_survey'));
            }
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
            'survey' => $companySurvey->getSurvey(),
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
     *     requirements={"page" = "\d+"}, defaults={"academicYear" = null, "page" = 1}, methods={"GET"})
     */
    public function studentListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
        \Symfony\Component\Security\Core\Security $security,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        return $this->agreementList(
            $request,
            $userExtensionService,
            $translator,
            $teacherRepository,
            $groupRepository,
            $security,
            $page,
            'work_linked_training_survey_student_form',
            'wlt/survey/student_list.html.twig',
            $academicYear
        );
    }

    /**
     * @Route("/empresa/{academicYear}/{page}", name="work_linked_training_survey_company_list",
     *     requirements={"page" = "\d+"}, defaults={"academicYear" = null, "page" = 1}, methods={"GET"})
     */
    public function companyListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
        \Symfony\Component\Security\Core\Security $security,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        return $this->agreementList(
            $request,
            $userExtensionService,
            $translator,
            $teacherRepository,
            $groupRepository,
            $security,
            $page,
            'work_linked_training_survey_company_form',
            'wlt/survey/company_list.html.twig',
            $academicYear
        );
    }

    /**
     * @param Request $request
     * @param UserExtensionService $userExtensionService
     * @param TranslatorInterface $translator
     * @param TeacherRepository $teacherRepository
     * @param GroupRepository $groupRepository
     * @param \Symfony\Component\Security\Core\Security $security
     * @param $page
     * @param $routeName
     * @param $template
     * @param AcademicYear $academicYear
     * @return Response
     */
    private function agreementList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
        \Symfony\Component\Security\Core\Security $security,
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
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->from(Agreement::class, 'a')
            ->leftJoin('a.workDays', 'wd')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->join('a.project', 'pr')
            ->leftJoin('pr.studentSurvey', 'ss')
            ->leftJoin('pr.companySurvey', 'cs')
            ->groupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
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
                ->setParameter('tq', '%' . $q . '%');
        }

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
        $isWorkTutor = $security->isGranted(WLTOrganizationVoter::WLT_WORK_TUTOR, $organization);

        if (false === $isManager && false === $isWltManager) {
            $person = $this->getUser()->getPerson();

            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor
            $teacher =
                $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

            if ($teacher) {
                $groups = $groupRepository->findByAcademicYearAndTeacher($academicYear, $teacher);

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
                } else {
                    // es estudiante, sólo él
                    $queryBuilder
                        ->andWhere('p = :person')
                        ->setParameter('person', $person);
                }
            }
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($page);

        return $this->render($template, [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_survey',
            'route_name' => $routeName,
            'academic_year' => $academicYear
        ]);
    }
    /**
     * @Route("/seguimiento/cumplimentar/{id}",
     *     name="work_linked_training_survey_organization_form", methods={"GET", "POST"})
     */
    public function organizationFillAction(
        Request $request,
        TranslatorInterface $translator,
        \Symfony\Component\Security\Core\Security $security,
        Teacher $teacher
    ) {
        $organization = $teacher->getAcademicYear()->getOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_EDUCATIONAL_TUTOR, $organization);

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        if (!$isManager && !$isWltManager && $teacher->getPerson()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $readOnly = false;
        $teacherSurvey = $teacher->getWltTeacherSurvey();

        if ($teacherSurvey === null) {
            $teacherSurvey = new AnsweredSurvey();
            $survey = $teacher->getAcademicYear()->getWltOrganizationSurvey();

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

            $teacher->setWltTeacherSurvey($teacherSurvey);
        }

        $form = $this->createForm(AnsweredSurveyType::class, $teacherSurvey, [
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $teacherSurvey->setTimestamp(new \DateTime());
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_survey'));
                return $this->redirectToRoute('work_linked_training_survey_organization_list', [
                    'academicYear' => $teacher->getAcademicYear()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_survey'));
            }
        }

        $title = $translator->trans('title.fill', [], 'wlt_survey');

        $breadcrumb = [
            ['fixed' => $teacher . ' - ' . $teacher->getAcademicYear()->getOrganization()],
            ['fixed' => $title]
        ];

        return $this->render('wlt/survey/form.html.twig', [
            'menu_path' => 'work_linked_training_survey_organization_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'survey' => $teacherSurvey->getSurvey(),
            'teacher' => $teacher,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/seguimiento/{academicYear}/{page}", name="work_linked_training_survey_organization_list",
     *     requirements={"page" = "\d+"}, defaults={"academicYear" = null, "page" = 1}, methods={"GET"})
     */
    public function organizationListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        \Symfony\Component\Security\Core\Security $security,
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
            ->addSelect('p')
            ->from(Teacher::class, 't')
            ->join('t.person', 'p')
            ->join('p.user', 'u')
            ->join('t.academicYear', 'ay')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->setParameter('tq', '%' . $q . '%');
        }
        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        if (!$isManager && !$isWltManager) {
            // puede ser profesor o jefe de departamento (de momento sólo soportamos el primer caso)
            $queryBuilder
                ->andWhere('u = :user')
                ->setParameter('user', $this->getUser());
        }
        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->andWhere('t.wltEducationalTutor = :is_teacher')
            ->andWhere('ay.wltOrganizationSurvey IS NOT NULL')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('is_teacher', true);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($page);

        return $this->render('wlt/survey/teacher_list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_survey',
            'academic_year' => $academicYear
        ]);
    }
}
