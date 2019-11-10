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

use AppBundle\Entity\WLT\Project;
use AppBundle\Form\Type\WLT\ProjectStudentEnrollmentType;
use AppBundle\Form\Type\WLT\ProjectType;
use AppBundle\Repository\WLT\ProjectRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WLT\ProjectVoter;
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
 * @Route("/dual/proyecto")
 */
class ProjectController extends Controller
{
    /**
     * @Route("/listar/{page}", name="work_linked_training_project_list", requirements={"page" = "\d+"},
     *     defaults={"page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('p')
            ->distinct(true)
            ->from(Project::class, 'p')
            ->leftJoin('p.manager', 'm')
            ->join('p.groups', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'h')
            ->orderBy('p.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('p.name LIKE :tq')
                ->orWhere('p.name LIKE :tq')
                ->orWhere('m.first_name LIKE :tq')
                ->orWhere('m.last_name LIKE :tq')
                ->orWhere('m.unique_identifier LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('p.organization = :organization')
            ->setParameter('organization', $organization);

        if (!$isManager) {
            $queryBuilder
                ->andWhere('p.manager = :manager OR (d.head IS NOT NULL AND h.person = :manager)')
                ->setParameter('manager', $this->getUser()->getPerson());
        }

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($page);

        $title = $this->get('translator')->trans('title.list', [], 'wlt_project');

        return $this->render('wlt/project/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_project'
        ]);
    }

    /**
     * @Route("/nuevo", name="work_linked_training_project_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $project = new Project();
        $project
            ->setOrganization($organization);

        if (!$isManager) {
            $project
                ->setManager($this->getUser()->getPerson());
        }

        $this->getDoctrine()->getManager()->persist($project);

        return $this->editAction($request, $userExtensionService, $translator, $project);
    }

    /**
     * @Route("/{id}", name="work_linked_training_project_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function editAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $organization = $userExtensionService->getCurrentOrganization();
        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(ProjectType::class, $project, [
            'lock_manager' => !$isManager
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_project'));
                return $this->redirectToRoute('work_linked_training_project_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_project'));
            }
        }

        $title = $translator->trans(
            $project->getId() ? 'title.edit' : 'title.new',
            [],
            'wlt_project'
        );

        $breadcrumb = [
            $project->getId() ?
                ['fixed' => $project->getName()] :
                ['fixed' => $translator->trans('title.new', [], 'wlt_project')]
        ];

        return $this->render('wlt/project/form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/estudiantes/{id}", name="work_linked_training_project_student_enrollment",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function studentEnrollmentsAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Project $project
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(ProjectStudentEnrollmentType::class, $project, [
            'groups' => $project->getGroups()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_project'));
                return $this->redirectToRoute('work_linked_training_project_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_project'));
            }
        }

        $title = $translator->trans(
            'title.student_enrollment',
            [],
            'wlt_project'
        );

        $breadcrumb = [
            ['fixed' => $project->getName()],
            ['fixed' => $translator->trans('title.student_enrollment', [], 'wlt_project')]
        ];

        return $this->render('wlt/project/student_enrollment_form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/eliminar", name="work_linked_training_project_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        ProjectRepository $projectRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('work_linked_training_project_list');
        }
        $selectedItems = $projectRepository->findAllInListByIdAndOrganization($items, $organization);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $projectRepository->deleteFromList($selectedItems);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_project'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_project'));
            }
            return $this->redirectToRoute('work_linked_training_project_list');
        }

        $title = $this->get('translator')->trans('title.delete', [], 'wlt_project');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wlt/project/delete.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $selectedItems
        ]);
    }
}
