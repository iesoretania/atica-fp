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
use App\Form\Type\Edu\PerformanceScaleValueType;
use App\Repository\Edu\PerformanceScaleValueRepository;
use App\Security\Edu\PerformanceScaleVoter;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/centro/escala/valor')]
class PerformanceScaleValueController extends AbstractController
{
    #[Route(path: '/nueva/{id}', name: 'organization_performance_scale_value_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        PerformanceScale $performanceScale
    ): Response
    {
        $this->denyAccessUnlessGranted(PerformanceScaleVoter::MANAGE, $performanceScale);

        $performanceScaleValue = new PerformanceScaleValue();
        $performanceScaleValue
            ->setPerformanceScale($performanceScale);

        $managerRegistry->getManager()->persist($performanceScaleValue);

        return $this->form($request, $translator, $managerRegistry, $performanceScaleValue);
    }

    #[Route(path: '/{id}', name: 'organization_performance_scale_value_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function form(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        PerformanceScaleValue $performanceScaleValue
    ): Response {
        $this->denyAccessUnlessGranted(PerformanceScaleVoter::MANAGE, $performanceScaleValue->getPerformanceScale());

        $form = $this->createForm(PerformanceScaleValueType::class, $performanceScaleValue);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_performance_scale_value'));
                return $this->redirectToRoute('organization_performance_scale_value_list', [
                    'id' => $performanceScaleValue->getPerformanceScale()->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_performance_scale_value'));
            }
        }

        $title = $translator->trans(
            $performanceScaleValue->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_performance_scale_value'
        );

        $breadcrumb = [
            [
                'fixed' => $performanceScaleValue->getPerformanceScale()->getDescription(),
                'routeName' => 'organization_performance_scale_edit',
                'routeParams' => ['id' => $performanceScaleValue->getPerformanceScale()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.list', [], 'edu_performance_scale_value'),
                'routeName' => 'organization_performance_scale_value_list',
                'routeParams' => ['id' => $performanceScaleValue->getPerformanceScale()->getId()]
            ],
            $performanceScaleValue->getId() !== null ?
                ['fixed' => $performanceScaleValue->getDescription()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_performance_scale_value')]
        ];

        return $this->render('organization/performance_scale/value_form.html.twig', [
            'menu_path' => 'organization_performance_scale_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/{id}/listar/{page}/', name: 'organization_performance_scale_value_list', requirements: ['page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function list(
        Request                         $request,
        TranslatorInterface             $translator,
        PerformanceScaleValueRepository $performanceScaleValueRepository,
        PerformanceScale                $performanceScale,
        int                             $page = 1
    ): Response {
        $this->denyAccessUnlessGranted(PerformanceScaleVoter::MANAGE, $performanceScale);

        $q = $request->get('q');

        $queryBuilder = $performanceScaleValueRepository->findByPerformanceScaleAndPartialStringQueryBuilder($performanceScale, $q);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'edu_performance_scale_value');
        $breadcrumb = [
            [
                'fixed' => $performanceScale->getDescription(),
                'routeName' => 'organization_performance_scale_list',
                'routeParams' => []
            ],
            ['fixed' => $title]
        ];

        return $this->render('organization/performance_scale/value_list.html.twig', [
            'menu_path' => 'organization_performance_scale_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_performance_scale_value',
            'performance_scale' => $performanceScale
        ]);
    }

    #[Route(path: '/{id}/eliminar', name: 'organization_performance_scale_value_operation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request                         $request,
        PerformanceScaleValueRepository $performanceScaleValueRepository,
        TranslatorInterface             $translator,
        ManagerRegistry                 $managerRegistry,
        PerformanceScale                $performanceScale
    ): Response {
        $this->denyAccessUnlessGranted(PerformanceScaleVoter::MANAGE, $performanceScale);

        $em = $managerRegistry->getManager();

        $items = $request->request->all('items');
        if (count($items) === 0) {
            return $this->redirectToRoute('organization_performance_scale_value_list', [
                'id' => $performanceScale->getId()
            ]);
        }

        $grades = $performanceScaleValueRepository->findAllInListByIdAndPerformanceScale($items, $performanceScale);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $performanceScaleValueRepository->deleteFromList($grades);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_performance_scale_value'));
            } catch (\Exception) {
                $this->addFlash(
                    'error',
                    $translator->trans('message.delete_error', [], 'edu_performance_scale_value')
                );
            }
            return $this->redirectToRoute('organization_performance_scale_value_list', [
                'id' => $performanceScale->getId()
            ]);
        }

        $breadcrumb = [
            [
                'fixed' => $translator->trans('title.list', [], 'edu_performance_scale_value'),
                'routeName' => 'organization_performance_scale_value_list',
                'routeParams' => ['id' => $performanceScale->getId()]
            ],
            ['fixed' => $translator->trans('title.delete', [], 'edu_performance_scale_value')]
        ];

        return $this->render('organization/performance_scale/value_delete.html.twig', [
            'menu_path' => 'work_linked_training_evaluation_list',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'edu_performance_scale_value'),
            'items' => $grades
        ]);
    }
}
