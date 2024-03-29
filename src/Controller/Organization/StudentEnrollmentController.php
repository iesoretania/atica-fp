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
use App\Entity\Edu\StudentEnrollment;
use App\Form\Type\Edu\StudentEnrollmentType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\StudentEnrollmentRepository;
use App\Security\Edu\AcademicYearVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/centro/matricula")
 */
class StudentEnrollmentController extends AbstractController
{
    /**
     * @Route("/{id}", name="organization_student_enrollment_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        StudentEnrollment $studentEnrollment
    ) {
        $em = $this->getDoctrine()->getManager();

        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(
            OrganizationVoter::MANAGE,
            $organization
        );

        $form = $this->createForm(StudentEnrollmentType::class, $studentEnrollment, [
            'academic_year' => $studentEnrollment->getGroup()->getGrade()->getTraining()->getAcademicYear()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_student_enrollment'));
                return $this->redirectToRoute('organization_teacher_list', [
                    'academicYear' => $studentEnrollment->getGroup()->getGrade()->getTraining()->getAcademicYear()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_student_enrollment'));
            }
        }

        $title = $translator->trans('title.edit', [], 'edu_student_enrollment');

        $breadcrumb = [
            ['fixed' => (string) $studentEnrollment]
        ];

        return $this->render('organization/student_enrollment/form.html.twig', [
            'menu_path' => 'organization_student_enrollment_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="organization_student_enrollment_list",
     *     requirements={"page" = "\d+"}, defaults={"academicYear" = null, "page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        TranslatorInterface $translator,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('se')
            ->addSelect('p')
            ->addSelect('g')
            ->from(StudentEnrollment::class, 'se')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->innerJoin('se.person', 'p')
            ->innerJoin('se.group', 'g')
            ->innerJoin('g.grade', 'gr')
            ->innerJoin('gr.training', 't');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->andWhere('p.id = :q')
                ->orWhere('p.loginUsername LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.emailAddress LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->orWhere('p.uniqueIdentifier LIKE :tq')
                ->setParameter('tq', '%'.$q.'%')
                ->setParameter('q', $q);
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

        $title = $translator->trans('title.list', [], 'edu_student_enrollment');

        return $this->render('organization/student_enrollment/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_student_enrollment',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/operacion/{academicYear}", name="organization_student_enrollment_operation", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(
            OrganizationVoter::MANAGE,
            $userExtensionService->getCurrentOrganization()
        );
        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            return $this->redirectToRoute(
                'organization_student_enrollment_list',
                [
                    'academicYear' => $academicYear->getId()
                ]
            );
        }

        $studentEnrollments = $studentEnrollmentRepository->findInListByAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $studentEnrollmentRepository->deleteFromList($studentEnrollments);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_student_enrollment'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_student_enrollment'));
            }
            return $this->redirectToRoute(
                'organization_student_enrollment_list',
                [
                    'academicYear' => $academicYear->getId()
                ]
            );
        }

        return $this->render('organization/student_enrollment/delete.html.twig', [
            'menu_path' => 'organization_student_enrollment_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_student_enrollment')]],
            'title' => $translator->trans('title.delete', [], 'edu_student_enrollment'),
            'items' => $studentEnrollments
        ]);
    }
}
