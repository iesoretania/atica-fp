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
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Form\Type\Edu\TeacherType;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Security\Edu\AcademicYearVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/centro/profesorado")
 */
class TeacherController extends Controller
{
    /**
     * @Route("/{id}", name="organization_teacher_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(Request $request, Teacher $teacher)
    {
        $em = $this->getDoctrine()->getManager();

        if (null === $teacher->getPerson()->getUser()) {
            return $this->redirectToRoute(
                'organization_teacher_list',
                [
                    'academic_year' => $teacher->getAcademicYear()
                ]
            );
        }

        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $teacher->getAcademicYear());

        $form = $this->createForm(TeacherType::class, $teacher);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'edu_teacher'));
                return $this->redirectToRoute('organization_teacher_list', [
                    'academicYear' => $teacher->getAcademicYear()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.error', [], 'edu_teacher'));
            }
        }

        $title = $this->get('translator')->trans('title.edit', [], 'edu_teacher');

        $breadcrumb = [
            ['fixed' => (string) $teacher->getPerson()]
        ];

        return $this->render('organization/teacher/form.html.twig', [
            'menu_path' => 'organization_teacher_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'user' => $teacher
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="organization_teacher_list", requirements={"page" = "\d+"},
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
            $academicYear = $this->getDoctrine()->getRepository(AcademicYear::class)->
                getCurrentByOrganization($organization);
        }
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from('AppBundle:Edu\Teacher', 't')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->innerJoin('t.person', 'p')
            ->innerJoin('p.user', 'u');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->andWhere('u.id = :q')
                ->orWhere('u.loginUsername LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('u.emailAddress LIKE :tq')
                ->setParameter('tq', '%'.$q.'%')
                ->setParameter('q', $q);
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'edu_teacher');

        return $this->render('organization/teacher/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_teacher',
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/eliminar/{academicYear}", name="organization_teacher_delete", methods={"POST"})
     */
    public function deleteAction(Request $request, TeacherRepository $teacherRepository, AcademicYear $academicYear)
    {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('users', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('organization_teacher_list', ['academicYear' => $academicYear->getId()]);
        }

        $teachers = $teacherRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $em->createQueryBuilder()
                    ->delete(Teacher::class, 't')
                    ->where('t IN (:items)')
                    ->setParameter('items', $items)
                    ->getQuery()
                    ->execute();

                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'edu_teacher'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'edu_teacher'));
            }
            return $this->redirectToRoute('organization_teacher_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/teacher/delete.html.twig', [
            'menu_path' => 'organization_teacher_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'edu_teacher')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'edu_teacher'),
            'teachers' => $teachers
        ]);
    }
}
