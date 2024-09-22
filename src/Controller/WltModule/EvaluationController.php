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

namespace App\Controller\WltModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Grade;
use App\Entity\Person;
use App\Entity\WltModule\Agreement;
use App\Entity\WltModule\AgreementActivityRealization;
use App\Entity\WltModule\AgreementActivityRealizationComment;
use App\Form\Type\WltModule\AgreementActivityRealizationNewCommentType;
use App\Form\Type\WltModule\AgreementEvaluationType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\PerformanceScaleValueRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WltModule\GroupRepository as WltGroupRepository;
use App\Repository\WltModule\ProjectRepository;
use App\Security\OrganizationVoter;
use App\Security\WltModule\AgreementActivityRealizationCommentVoter;
use App\Security\WltModule\AgreementVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/dual/convenio/evaluar')]
class EvaluationController extends AbstractController
{
    #[Route(path: '/{id}', name: 'work_linked_training_evaluation_form', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request                         $request,
        TranslatorInterface             $translator,
        PerformanceScaleValueRepository $performanceScaleValueRepository,
        ManagerRegistry                 $managerRegistry,
        Agreement                       $agreement
    ): Response {
        $this->denyAccessUnlessGranted(AgreementVoter::VIEW_GRADE, $agreement);

        $academicYear = $agreement->
            getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();

        $em = $managerRegistry->getManager();

        $readOnly = !$this->isGranted(AgreementVoter::GRADE, $agreement);

        $form = $this->createForm(AgreementEvaluationType::class, $agreement, [
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        $grades = $performanceScaleValueRepository->findByPerformanceScale($agreement->getProject()->getPerformanceScale());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_agreement'));
                return $this->redirectToRoute('work_linked_training_evaluation_list', [
                    'academicYear' => $academicYear->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_agreement'));
            }
        }

        $title = $translator->trans('title.grade', [], 'wlt_agreement_activity_realization');

        $breadcrumb = [
            ['fixed' => $agreement->__toString()],
            ['fixed' => $title]
        ];

        return $this->render('wlt/evaluation/form.html.twig', [
            'menu_path' => 'work_linked_training_evaluation_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'grades' => $grades,
            'agreement' => $agreement,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{academicYear}/{page}', name: 'work_linked_training_evaluation_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                $request,
        UserExtensionService   $userExtensionService,
        TranslatorInterface    $translator,
        WltGroupRepository     $wltGroupRepository,
        ProjectRepository      $projectRepository,
        AcademicYearRepository $academicYearRepository,
        TeacherRepository      $teacherRepository,
        ManagerRegistry        $managerRegistry,
        int                    $page = 1,
        AcademicYear           $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_VIEW_EVALUATION, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->addSelect('COUNT(ear)')
            ->addSelect('COUNT(ear.grade)')
            ->addSelect('pro')
            ->from(Agreement::class, 'a')
            ->leftJoin('a.evaluatedActivityRealizations', 'ear')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->leftJoin('a.additionalWorkTutor', 'awt')
            ->join('a.educationalTutor', 'et')
            ->leftJoin('a.additionalEducationalTutor', 'aet')
            ->join('a.project', 'pro')
            ->groupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->orWhere('wt.firstName LIKE :tq')
                ->orWhere('wt.lastName LIKE :tq')
                ->orWhere('wt.uniqueIdentifier LIKE :tq')
                ->orWhere('awt.firstName LIKE :tq')
                ->orWhere('awt.lastName LIKE :tq')
                ->orWhere('awt.uniqueIdentifier LIKE :tq')
                ->orWhere('pro.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }


        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization);

        $groups = [];
        $projects = [];

        /** @var Person $person */
        $person = $this->getUser();

        // Dar acceso a sus estudiantes si es profesor/a de algún grupo
        $teacher =
            $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        if (!$isWltManager && !$isManager) {
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, docente o tutor de grupo -> ver los acuerdos de los
            // estudiantes de sus grupos
            $groups = $wltGroupRepository
                ->findByAcademicYearAndGroupTutorOrDepartmentHeadPerson($academicYear, $person);

            if ($teacher !== null) {
                $groups = array_merge($groups->toArray(), $wltGroupRepository
                    ->findByAcademicYearAndPerson($academicYear, $teacher->getPerson())->toArray());
            }
        } elseif ($isWltManager) {
            $projects = $projectRepository->findByManager($person);
        }

        // ver siempre las propias
        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups) OR se.person = :person ' .
                           'OR a.workTutor = :person OR a.additionalWorkTutor = :person ' .
                           'OR et.person = :person OR aet.person = :person')
                ->setParameter('groups', $groups)
                ->setParameter('person', $person);
        }
        if ($projects) {
            $queryBuilder
                ->andWhere('pro IN (:projects) OR se.person = :person ' .
                           'OR a.workTutor = :person OR a.additionalWorkTutor = :person ' .
                           'OR et.person = :person OR aet.person = :person')
                ->setParameter('projects', $projects)
                ->setParameter('person', $person);
        }

        if (!$isWltManager && !$isManager && !$projects && !$groups) {
            $queryBuilder
                ->andWhere('se.person = :person OR a.workTutor = :person OR a.additionalWorkTutor = :person ' .
                           'OR et.person = :person OR aet.person = :person')
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
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'wlt_agreement_activity_realization');

        return $this->render('wlt/evaluation/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_agreement_activity_realization',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/comentarios/{id}', name: 'work_linked_training_evaluation_comment_form', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function comment(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AgreementActivityRealization $agreementActivityRealization
    ): Response {
        $agreement = $agreementActivityRealization->getAgreement();
        $this->denyAccessUnlessGranted(AgreementVoter::VIEW_GRADE, $agreement);

        $em = $managerRegistry->getManager();

        $readOnly = !$this->isGranted(AgreementVoter::GRADE, $agreement);

        $form = $this->createForm(AgreementActivityRealizationNewCommentType::class, $agreementActivityRealization, [
            'disabled' => $readOnly,
            'can_be_disabled' => !$agreementActivityRealization->getGrade() instanceof Grade
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $newComment = null;
                if (trim($form->get('newComment')->getData()->__toString()) !== '') {
                    $newComment = new AgreementActivityRealizationComment();
                    $em->persist($newComment);
                    $newComment
                        ->setAgreementActivityRealization($agreementActivityRealization)
                        ->setComment(trim($form->get('newComment')->getData()->__toString()))
                        ->setTimestamp(new \DateTime())
                        ->setPerson($this->getUser());
                }
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [],
                    'wlt_agreement_activity_realization'));

                if (!$newComment instanceof AgreementActivityRealizationComment) {
                    return $this->redirectToRoute('work_linked_training_evaluation_form', [
                        'id' => $agreement->getId()
                    ]);
                }

                return $this->redirectToRoute('work_linked_training_evaluation_comment_form', [
                    'id' => $agreementActivityRealization->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [],
                    'wlt_agreement_activity_realization'));
            }
        }

        $title = $translator->trans('title.comment', [], 'wlt_agreement_activity_realization');

        $breadcrumb = [
            [
                'fixed' => (string) $agreement,
                'routeName' => 'work_linked_training_evaluation_form',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $agreementActivityRealization->getActivityRealization()->__toString()],
            ['fixed' => $title]
        ];

        return $this->render('wlt/evaluation/comment_form.html.twig', [
            'menu_path' => 'work_linked_training_evaluation_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'agreement_activity_realization' => $agreementActivityRealization,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/comentarios/eliminar/{id}', name: 'work_linked_training_evaluation_comment_delete', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function deleteComment(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AgreementActivityRealizationComment $agreementActivityRealizationComment
    ): Response {
        $this->denyAccessUnlessGranted(AgreementActivityRealizationCommentVoter::DELETE,
            $agreementActivityRealizationComment);

        $em = $managerRegistry->getManager();

        $agreement = $agreementActivityRealizationComment->getAgreementActivityRealization()->getAgreement();

        if ($request->get('confirm', '') === 'ok') {
            try {
                $em->remove($agreementActivityRealizationComment);
                $em->flush();
                $this->addFlash('success', $translator->trans('message.comment_deleted', [],
                    'wlt_agreement_activity_realization'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.comment_delete_error', [],
                    'wlt_agreement_activity_realization'));
            }
            return $this->redirectToRoute('work_linked_training_evaluation_comment_form', [
                'id' => $agreementActivityRealizationComment->getAgreementActivityRealization()->getId()
            ]);
        }

        $title = $translator->trans('title.delete_comment', [], 'wlt_agreement_activity_realization');

        $breadcrumb = [
            [
                'fixed' => (string) $agreement,
                'routeName' => 'work_linked_training_evaluation_form',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            [
                'fixed' => $agreementActivityRealizationComment
                    ->getAgreementActivityRealization()->getActivityRealization()->__toString(),
                'routeName' => 'work_linked_training_evaluation_comment_form',
                'routeParams' => ['id' => $agreementActivityRealizationComment
                    ->getAgreementActivityRealization()->getId()]
            ],
            [
                'fixed' => $title
            ]
        ];

        return $this->render('wlt/evaluation/comment_delete.html.twig', [
            'menu_path' => 'work_linked_training_evaluation_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'comment' => $agreementActivityRealizationComment,
            'agreement' => $agreement,
            'agreement_activity_realization' => $agreementActivityRealizationComment->getAgreementActivityRealization()
        ]);
    }
}
