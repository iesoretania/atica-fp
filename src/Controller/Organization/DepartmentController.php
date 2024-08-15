<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Controller\Organization;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Department;
use App\Form\Type\Edu\DepartmentType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\DepartmentRepository;
use App\Security\Edu\AcademicYearVoter;
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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/centro/departamento')]
class DepartmentController extends AbstractController
{
    #[Route(path: '/nuevo', name: 'organization_department_new', methods: ['GET', 'POST'])]
    #[Route(path: '/{id}', name: 'organization_department_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Department $department = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $managerRegistry->getManager();

        if (!$department instanceof Department) {
            $department = new Department();
            $department
                ->setAcademicYear($organization->getCurrentAcademicYear());
            $em->persist($department);
        }

        $form = $this->createForm(DepartmentType::class, $department, [
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_department'));
                return $this->redirectToRoute('organization_department_list', [
                    'academicYear' => $department->getAcademicYear()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_department'));
            }
        }

        $title = $translator->trans(
            $department->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_department'
        );

        $breadcrumb = [
            $department->getId() !== null ?
                ['fixed' => $department->getName()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_department')]
        ];

        return $this->render('organization/department/form.html.twig', [
            'menu_path' => 'organization_department_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{academicYear}/{page}', name: 'organization_department_list', requirements: ['page' => '\d+'], defaults: ['academicYear' => null, 'page' => 1], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        int $page = 1,
        AcademicYear $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('d')
            ->from(Department::class, 'd')
            ->orderBy('d.name');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('d.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('d.academicYear = :academic_year')
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

        $title = $translator->trans('title.list', [], 'edu_department');

        return $this->render('organization/department/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_department',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/eliminar/{academicYear}', name: 'organization_department_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        TranslatorInterface $translator,
        DepartmentRepository $departmentRepository,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear
    ): Response {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        $em = $managerRegistry->getManager();

        $items = $request->request->all('items');
        if ((is_countable($items) ? count($items) : 0) === 0) {
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
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_department'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_department'));
            }
            return $this->redirectToRoute('organization_department_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/department/delete.html.twig', [
            'menu_path' => 'organization_department_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_department')]],
            'title' => $translator->trans('title.delete', [], 'edu_department'),
            'departments' => $departments
        ]);
    }
}
