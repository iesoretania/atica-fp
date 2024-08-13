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
use App\Entity\Edu\Teacher;
use App\Form\Type\Edu\NewTeacherType;
use App\Form\Type\Edu\TeacherType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TeacherRepository;
use App\Security\Edu\AcademicYearVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/centro/profesorado')]
class TeacherController extends AbstractController
{
    #[Route(path: '/{id}', name: 'organization_teacher_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Teacher $teacher
    ) {
        $em = $managerRegistry->getManager();

        if (null === $teacher->getPerson()) {
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
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_teacher'));
                return $this->redirectToRoute('organization_teacher_list', [
                    'academicYear' => $teacher->getAcademicYear()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_teacher'));
            }
        }

        $title = $translator->trans('title.edit', [], 'edu_teacher');

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

    #[Route(path: '/nuevo', name: 'organization_teacher_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        TeacherRepository $teacherRepository
    )
    {
        $em = $managerRegistry->getManager();

        $academicYear = $userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();

        $teacher = new Teacher();
        $teacher->setAcademicYear($academicYear);
        $em->persist($teacher);

        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $teacher->getAcademicYear());

        $form = $this->createForm(NewTeacherType::class, $teacher);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $otherTeacher = $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $teacher->getPerson());
                if ($otherTeacher === null) {
                    $teacher->getPerson()->setExternalCheck(true);
                    $teacher->getPerson()->setAllowExternalCheck(true);
                    $em->flush();
                    $this->addFlash('success', $translator->trans('message.saved', [], 'edu_teacher'));
                    return $this->redirectToRoute('organization_teacher_list', [
                        'academicYear' => $teacher->getAcademicYear()
                    ]);
                } else {
                    $this->addFlash('error', $translator->trans('message.repeated_error', [], 'edu_teacher'));
                }
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_teacher'));
            }
        }

        $title = $translator->trans('title.new', [], 'edu_teacher');

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

    #[Route(path: '/listar/{academicYear}/{page}', name: 'organization_teacher_list', requirements: ['page' => '\d+'], defaults: ['academicYear' => null, 'page' => 1], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        int $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->addSelect('p')
            ->from(Teacher::class, 't')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->innerJoin('t.person', 'p');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->andWhere('p.id = :q')
                ->orWhere('p.loginUsername LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.emailAddress LIKE :tq')
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
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'edu_teacher');

        return $this->render('organization/teacher/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_teacher',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/eliminar/{academicYear}', name: 'organization_teacher_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        TeacherRepository $teacherRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('users', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
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
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_teacher'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_teacher'));
            }
            return $this->redirectToRoute('organization_teacher_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/teacher/delete.html.twig', [
            'menu_path' => 'organization_teacher_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_teacher')]],
            'title' => $translator->trans('title.delete', [], 'edu_teacher'),
            'teachers' => $teachers
        ]);
    }
}
