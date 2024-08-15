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
use App\Entity\Edu\NonWorkingDay;
use App\Form\Type\Edu\NonWorkingDayType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\NonWorkingDayRepository;
use App\Security\Edu\AcademicYearVoter;
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

#[Route(path: '/centro/dia_no_lectivo')]
class NonWorkingDayController extends AbstractController
{
    #[Route(path: '/nuevo/{academicYear}', name: 'organization_non_working_day_new', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear
    ): Response
    {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);


        $nonWorkingDay = new NonWorkingDay();
        $nonWorkingDay
            ->setAcademicYear($academicYear);

        $managerRegistry->getManager()->persist($nonWorkingDay);

        return $this->index($request, $translator, $managerRegistry, $nonWorkingDay);
    }

    #[Route(path: '/{id}', name: 'organization_non_working_day_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        NonWorkingDay $nonWorkingDay
    ): Response {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $nonWorkingDay->getAcademicYear());

        $em = $managerRegistry->getManager();

        $form = $this->createForm(NonWorkingDayType::class, $nonWorkingDay);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_non_working_day'));
                return $this->redirectToRoute('organization_non_working_day_list', [
                    'academicYear' => $nonWorkingDay->getAcademicYear()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_non_working_day'));
            }
        }

        $title = $translator->trans(
            $nonWorkingDay->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_non_working_day'
        );

        $breadcrumb = [
            ['fixed' => $nonWorkingDay->getAcademicYear()->getDescription()],
            $nonWorkingDay->getId() !== null ?
                ['fixed' => $nonWorkingDay->getDate()->format($translator->trans('format.date', [], 'general'))] :
                ['fixed' => $translator->trans('title.new', [], 'edu_non_working_day')]
        ];

        return $this->render('organization/non_working_day/form.html.twig', [
            'menu_path' => 'organization_non_working_day_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{academicYear}/{page}', name: 'organization_non_working_day_list', requirements: ['page' => '\d+'], defaults: ['academicYear' => null, 'page' => 1], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        TranslatorInterface $translator,
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
            ->select('n')
            ->from(NonWorkingDay::class, 'n')
            ->orderBy('n.date');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->where('n.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('n.academicYear = :academic_year')
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

        $title = $translator->trans('title.list', [], 'edu_non_working_day');

        return $this->render('organization/non_working_day/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_non_working_day',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/eliminar/{academicYear}', name: 'organization_non_working_day_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        UserExtensionService $userExtensionService,
        NonWorkingDayRepository $nonWorkingDayRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear
    ): Response {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        if ($academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            throw $this->createNotFoundException();
        }

        $em = $managerRegistry->getManager();

        $items = $request->request->all('items');
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_non_working_day_list', ['academicYear' => $academicYear->getId()]);
        }

        $nonWorkingDays = $nonWorkingDayRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $nonWorkingDayRepository->deleteFromList($nonWorkingDays);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_non_working_day'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_non_working_day'));
            }
            return $this->redirectToRoute('organization_non_working_day_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/non_working_day/delete.html.twig', [
            'menu_path' => 'organization_non_working_day_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_non_working_day')]],
            'title' => $translator->trans('title.delete', [], 'edu_non_working_day'),
            'items' => $nonWorkingDays
        ]);
    }
}
