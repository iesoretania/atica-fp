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
use AppBundle\Entity\Edu\Grade;
use AppBundle\Form\Type\Edu\GradeType;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\GradeRepository;
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
 * @Route("/centro/nivel")
 */
class GradeController extends Controller
{
    /**
     * @Route("/nuevo", name="organization_grade_new", methods={"GET", "POST"})
     * @Route("/{id}", name="organization_grade_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(Request $request, UserExtensionService $userExtensionService, Grade $grade = null)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $grade) {
            $grade = new Grade();
            $em->persist($grade);
        }

        $form = $this->createForm(GradeType::class, $grade);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'edu_grade'));
                return $this->redirectToRoute('organization_grade_list', [
                    'academicYear' => $grade->getTraining()->getAcademicYear()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.error', [], 'edu_grade'));
            }
        }

        $title = $this->get('translator')->trans(
            $grade->getId() ? 'title.edit' : 'title.new',
            [],
            'edu_grade'
        );

        $breadcrumb = [
            $grade->getId() ?
                ['fixed' => $grade->getName()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'edu_grade')]
        ];

        return $this->render('organization/grade/form.html.twig', [
            'menu_path' => 'organization_grade_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="organization_grade_list", requirements={"page" = "\d+"},
     *     defaults={"academicYear" = null, "page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        if (null === $academicYear) {
            $academicYear = $academicYearRepository->getCurrentByOrganization($organization);
        }
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('g')
            ->from(Grade::class, 'g')
            ->orderBy('g.name')
            ->innerJoin('g.training', 't');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('g.name LIKE :tq')
                ->orWhere('t.name LIKE :tq')
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

        $title = $this->get('translator')->trans('title.list', [], 'edu_grade');

        return $this->render('organization/grade/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_grade',
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/eliminar/{academicYear}", name="organization_grade_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        GradeRepository $gradeRepository,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('organization_grade_list', ['academicYear' => $academicYear->getId()]);
        }

        $grades = $gradeRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            dump($grades);
            try {
                $em->createQueryBuilder()
                    ->delete(Grade::class, 'g')
                    ->where('g IN (:items)')
                    ->setParameter('items', $grades)
                    ->getQuery()
                    ->execute();

                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'edu_grade'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'edu_grade'));
            }
            return $this->redirectToRoute('organization_grade_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/grade/delete.html.twig', [
            'menu_path' => 'organization_grade_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'edu_grade')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'edu_grade'),
            'grades' => $grades
        ]);
    }
}
