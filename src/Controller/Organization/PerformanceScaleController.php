<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

use App\Entity\Edu\PerformanceScale;
use App\Entity\Edu\PerformanceScaleValue;
use App\Form\Type\Edu\PerformanceScaleType;
use App\Repository\Edu\PerformanceScaleRepository;
use App\Repository\Edu\PerformanceScaleValueRepository;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/centro/escala')]
class PerformanceScaleController extends AbstractController
{
    #[Route(path: '/nueva', name: 'organization_performance_scale_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        UserExtensionService $userExtensionService
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $performanceScale = new PerformanceScale();
        $performanceScale
            ->setEnabled(true)
            ->setOrganization($organization);

        $managerRegistry->getManager()->persist($performanceScale);

        return $this->form($request, $translator, $managerRegistry, $userExtensionService, $performanceScale);
    }

    #[Route(path: '/{id}', name: 'organization_performance_scale_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function form(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        UserExtensionService $userExtensionService,
        PerformanceScaleRepository $performanceScaleRepository,
        PerformanceScaleValueRepository $performanceScaleValueRepository,
        PerformanceScale $performanceScale
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $form = $this->createForm(PerformanceScaleType::class, $performanceScale);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Comprobar si es necesario copiar de otra encuesta
                if ($form->has('copyFrom') && $form->get('copyFrom')->getData()) {
                    /** @var PerformanceScale $original */
                    $original = $form->get('copyFrom')->getData();
                    $values = $performanceScaleValueRepository->findByPerformanceScale($original);
                    foreach ($values as $value) {
                        $newQuestion = new PerformanceScaleValue();
                        $newQuestion
                            ->setPerformanceScale($performanceScale)
                            ->setDescription($value->getDescription())
                            ->setNotes($value->getNotes())
                            ->setNumericGrade($value->getNumericGrade());
                        $performanceScaleValueRepository->persist($newQuestion);
                    }
                    $performanceScaleValueRepository->flush();
                }
                $performanceScaleRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_performance_scale'));
                return $this->redirectToRoute('organization_performance_scale_list');
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_performance_scale'));
            }
        }

        $title = $translator->trans(
            $performanceScale->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_performance_scale'
        );

        $breadcrumb = [
            $performanceScale->getId() !== null ?
                ['fixed' => $performanceScale->getDescription()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_performance_scale')]
        ];

        return $this->render('organization/performance_scale/form.html.twig', [
            'menu_path' => 'organization_performance_scale_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{page}/', name: 'organization_performance_scale_list', requirements: ['page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]    public function list(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        UserExtensionService $userExtensionService,
        PerformanceScaleRepository $performanceScaleRepository,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $q = $request->get('q');

        $queryBuilder = $performanceScaleRepository->findByOrganizationAndPartialStringQueryBuilder($organization, $q);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'edu_performance_scale');

        return $this->render('organization/performance_scale/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_performance_scale'
        ]);
    }

    #[Route(path: '/eliminar', name: 'organization_performance_scale_operation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request                    $request,
        PerformanceScaleRepository $performanceScaleRepository,
        TranslatorInterface        $translator,
        ManagerRegistry            $managerRegistry,
        UserExtensionService       $userExtensionService
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $items = $request->request->all('items');
        if (count($items) === 0) {
            return $this->redirectToRoute('organization_performance_scale_list');
        }

        $performanceScales = $performanceScaleRepository->findAllInListByIdAndOrganization($items, $organization);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $performanceScaleRepository->deleteFromList($performanceScales);

                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_performance_scale'));
            } catch (\Exception) {
                $this->addFlash(
                    'error',
                    $translator->trans('message.delete_error', [], 'edu_performance_scale')
                );
            }
            return $this->redirectToRoute('organization_performance_scale_list');
        }

        return $this->render('organization/performance_scale/delete.html.twig', [
            'menu_path' => 'organization_performance_scale_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_performance_scale')]],
            'title' => $translator->trans('title.delete', [], 'edu_performance_scale'),
            'items' => $performanceScales
        ]);
    }
}
