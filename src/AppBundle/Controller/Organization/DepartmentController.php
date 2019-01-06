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

namespace AppBundle\Controller\Organization;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Department;
use AppBundle\Form\Type\Edu\DepartmentType;
use AppBundle\Repository\Edu\DepartmentRepository;
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
 * @Route("/centro/departamento")
 */
class DepartmentController extends Controller
{
    /**
     * @Route("/nuevo", name="organization_department_new", methods={"GET", "POST"})
     * @Route("/{id}", name="organization_department_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        Department $department = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $department) {
            $department = new Department();
            $department
                ->setAcademicYear($organization->getCurrentAcademicYear());
            $em->persist($department);
        }

        $form = $this->createForm(DepartmentType::class, $department);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'edu_department'));
                return $this->redirectToRoute('organization_department_list', [
                    'academicYear' => $department->getAcademicYear()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.error', [], 'edu_department'));
            }
        }

        $title = $this->get('translator')->trans(
            $department->getId() ? 'title.edit' : 'title.new',
            [],
            'edu_department'
        );

        $breadcrumb = [
            $department->getId() ?
                ['fixed' => $department->getName()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'edu_department')]
        ];

        return $this->render('organization/department/form.html.twig', [
            'menu_path' => 'organization_department_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="organization_department_list", requirements={"page" = "\d+"},
     *     defaults={"academicYear" = null, "page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        if (null === $academicYear) {
            $organization = $userExtensionService->getCurrentOrganization();
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('d')
            ->from(Department::class, 'd')
            ->orderBy('d.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('d.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('d.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'edu_department');

        return $this->render('organization/department/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_department',
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/eliminar/{academicYear}", name="organization_department_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        DepartmentRepository $departmentRepository,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('organization_department_list', ['academicYear' => $academicYear->getId()]);
        }

        $departments = $departmentRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $em->createQueryBuilder()
                    ->delete(Department::class, 'd')
                    ->where('d IN (:items)')
                    ->setParameter('items', $departments)
                    ->getQuery()
                    ->execute();

                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'edu_department'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'edu_department'));
            }
            return $this->redirectToRoute('organization_department_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/department/delete.html.twig', [
            'menu_path' => 'organization_department_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'edu_department')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'edu_department'),
            'departments' => $departments
        ]);
    }
}
