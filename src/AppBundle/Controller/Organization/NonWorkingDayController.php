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
use AppBundle\Entity\Edu\NonWorkingDay;
use AppBundle\Form\Type\Edu\NonWorkingDayType;
use AppBundle\Repository\Edu\NonWorkingDayRepository;
use AppBundle\Security\Edu\AcademicYearVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/centro/dia_no_lectivo")
 */
class NonWorkingDayController extends Controller
{
    /**
     * @Route("/nuevo/{academicYear}", name="organization_non_working_day_new",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);


        $nonWorkingDay = new NonWorkingDay();
        $nonWorkingDay
            ->setAcademicYear($academicYear);

        $this->getDoctrine()->getManager()->persist($nonWorkingDay);

        return $this->indexAction($request, $translator, $nonWorkingDay);
    }

    /**
     * @Route("/{id}", name="organization_non_working_day_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        NonWorkingDay $nonWorkingDay
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $nonWorkingDay->getAcademicYear());

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(NonWorkingDayType::class, $nonWorkingDay);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_non_working_day'));
                return $this->redirectToRoute('organization_non_working_day_list', [
                    'academicYear' => $nonWorkingDay->getAcademicYear()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_non_working_day'));
            }
        }

        $title = $translator->trans(
            $nonWorkingDay->getId() ? 'title.edit' : 'title.new',
            [],
            'edu_non_working_day'
        );

        $breadcrumb = [
            ['fixed' => $nonWorkingDay->getAcademicYear()->getDescription()],
            $nonWorkingDay->getId() ?
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

    /**
     * @Route("/listar/{academicYear}/{page}", name="organization_non_working_day_list", requirements={"page" = "\d+"},
     *     defaults={"academicYear" = null, "page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
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
            ->select('n')
            ->from(NonWorkingDay::class, 'n')
            ->orderBy('n.date');

        $q = $request->get('q', null);

        if ($q) {
            $queryBuilder
                ->where('n.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('n.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $translator->trans('title.list', [], 'edu_non_working_day');

        return $this->render('organization/non_working_day/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_non_working_day',
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/eliminar/{academicYear}", name="organization_non_working_day_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        UserExtensionService $userExtensionService,
        NonWorkingDayRepository $nonWorkingDayRepository,
        TranslatorInterface $translator,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        if ($academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            return $this->createNotFoundException();
        }

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('organization_non_working_day_list', ['academicYear' => $academicYear->getId()]);
        }

        $nonWorkingDays = $nonWorkingDayRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $nonWorkingDayRepository->deleteFromList($nonWorkingDays);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_non_working_day'));
            } catch (\Exception $e) {
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
