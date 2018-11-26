<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

namespace AppBundle\Controller\Organization;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Group;
use AppBundle\Form\Type\Edu\GroupType;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\GroupRepository;
use AppBundle\Security\Edu\AcademicYearVoter;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/centro/grupo")
 */
class GroupController extends Controller
{
    /**
     * @Route("/nuevo", name="organization_group_new", methods={"GET", "POST"})
     * @Route("/{id}", name="organization_group_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(Request $request, UserExtensionService $userExtensionService, Group $group = null)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $group) {
            $group = new Group();
            $em->persist($group);
        }

        $form = $this->createForm(GroupType::class, $group);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'edu_group'));
                return $this->redirectToRoute('organization_group_list', [
                    'academicYear' => $group->getGrade()->getTraining()->getAcademicYear()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.error', [], 'edu_group'));
            }
        }

        $title = $this->get('translator')->trans(
            $group->getId() ? 'title.edit' : 'title.new',
            [],
            'edu_group'
        );

        $breadcrumb = [
            $group->getId() ?
                ['fixed' => $group->getName()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'edu_group')]
        ];

        return $this->render('organization/group/form.html.twig', [
            'menu_path' => 'organization_group_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'group' => $group,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="organization_group_list", requirements={"page" = "\d+"},
     *     defaults={"academicYear" = null, "page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        if (null === $academicYear) {
            $organization = $userExtensionService->getCurrentOrganization();
            $academicYear = $academicYearRepository->getCurrentByOrganization($organization);
        }

        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('g')
            ->from(Group::class, 'g')
            ->orderBy('g.name')
            ->innerJoin('g.grade', 'gr')
            ->innerJoin('gr.training', 't');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('g.name LIKE :tq')
                ->orWhere('gr.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'edu_group');

        return $this->render('organization/group/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_group',
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/eliminar/{academicYear}", name="organization_group_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        GroupRepository $groupRepository,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('organization_group_list', ['academicYear' => $academicYear->getId()]);
        }

        $groups = $groupRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $em->createQueryBuilder()
                    ->delete(Group::class, 't')
                    ->where('t IN (:items)')
                    ->setParameter('items', $groups)
                    ->getQuery()
                    ->execute();

                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'edu_group'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'edu_group'));
            }
            return $this->redirectToRoute('organization_group_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/group/delete.html.twig', [
            'menu_path' => 'organization_group_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'edu_group')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'edu_group'),
            'groups' => $groups
        ]);
    }
}
