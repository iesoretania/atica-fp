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

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Form\Type\WLT\AgreementEvaluationType;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\WLT\ProjectRepository;
use AppBundle\Repository\WLT\WLTGroupRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WLT\AgreementVoter;
use AppBundle\Security\WLT\WLTOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/convenio/evaluar")
 */
class EvaluationController extends Controller
{
    /**
     * @Route("/{id}", name="work_linked_training_evaluation_form",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        Agreement $agreement
    ) {
        $this->denyAccessUnlessGranted(AgreementVoter::VIEW_GRADE, $agreement);

        $academicYear = $agreement->
            getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();

        $em = $this->getDoctrine()->getManager();

        $readOnly = false === $this->isGranted(AgreementVoter::GRADE, $agreement);

        $form = $this->createForm(AgreementEvaluationType::class, $agreement, [
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_agreement'));
                return $this->redirectToRoute('work_linked_training_evaluation_list', [
                    'academicYear' => $academicYear->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_agreement'));
            }
        }

        $title = $translator->trans('title.grade', [], 'wlt_agreement_activity_realization');

        $breadcrumb = [
            ['fixed' => (string) $agreement],
            ['fixed' => $title]
        ];

        return $this->render('wlt/evaluation/form.html.twig', [
            'menu_path' => 'work_linked_training_evaluation_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="work_linked_training_evaluation_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        WLTGroupRepository $wltGroupRepository,
        ProjectRepository $projectRepository,
        AcademicYearRepository $academicYearRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_VIEW_EVALUATION, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

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
            $groups = $wltGroupRepository->findByAcademicYearAndGrupTutorOrDepartmentHeadPerson($academicYear, $person);
        } elseif ($isWltManager) {
            $projects = $projectRepository->findByManager($person);
        }

        // ver siempre las propias
        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups) OR se.person = :person OR a.workTutor = :person')
                ->setParameter('groups', $groups)
                ->setParameter('person', $person);
        }
        if ($projects) {
            $queryBuilder
                ->andWhere('pro IN (:projects) OR se.person = :person OR a.workTutor = :person')
                ->setParameter('projects', $projects)
                ->setParameter('person', $person);
        }

        if (false === $isWltManager && false === $isManager && !$projects && !$groups) {
            $queryBuilder
                ->andWhere('se.person = :person OR a.workTutor = :person')
                ->setParameter('person', $person);
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (\PagerFanta\Exception\OutOfRangeCurrentPageException $e) {
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
}
