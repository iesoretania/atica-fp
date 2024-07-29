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

use App\Entity\Edu\ReportTemplate;
use App\Form\Type\Edu\ReportTemplateType;
use App\Repository\Edu\ReportTemplateRepository;
use App\Security\Edu\ReportTemplateVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/centro/plantilla")
 */
class ReportTemplateController extends AbstractController
{
    /**
     * @Route("/nueva", name="organization_report_template_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator
    ) {
        $template = new ReportTemplate();
        $template
            ->setOrganization($userExtensionService->getCurrentOrganization());
        $managerRegistry->getManager()->persist($template);

        return $this->editAction($request, $translator, $managerRegistry, $template);
    }

    /**
     * @Route("/{id}", name="organization_report_template_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function editAction(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        ReportTemplate $template
    ) {
        $this->denyAccessUnlessGranted(ReportTemplateVoter::EDU_REPORT_TEMPLATE_VIEW, $template);

        $readOnly = !$this->isGranted(ReportTemplateVoter::EDU_REPORT_TEMPLATE_MANAGE, $template);

        $form = $this->createForm(ReportTemplateType::class, $template, [
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        if (!$readOnly && $form->isSubmitted() && $form->isValid()) {
            try {
                $em = $managerRegistry->getManager();
                $file = $form->get('newData')->getData();
                $template->setData(fopen($file, 'rb'));
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_report_template'));
                return $this->redirectToRoute('organization_report_template_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_report_template'));
            }
        }

        $title = $translator->trans(
            $template->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_report_template'
        );

        $breadcrumb = [
            $template->getId() !== null ?
                ['fixed' => $template->getDescription()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_report_template')]
        ];

        return $this->render('organization/report_template/form.html.twig', [
            'menu_path' => 'organization_report_template_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/descarga/{id}", name="organization_report_template_download",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function downloadAction(
        ReportTemplate $template
    ) {
        $this->denyAccessUnlessGranted(ReportTemplateVoter::EDU_REPORT_TEMPLATE_VIEW, $template);

        return new StreamedResponse(function () use ($template) {
            fpassthru($template->getData());
            exit();
        },
        200,
        [
            'Content-Type' => 'application/pdf'
        ]);
    }

    /**
     * @Route("/listar/{page}", name="organization_report_template_list", requirements={"page" = "\d+"},
     *     methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        int $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('rt')
            ->from(ReportTemplate::class, 'rt')
            ->orderBy('rt.description');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('rt.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('rt.organization = :organization')
            ->setParameter('organization', $organization);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'edu_report_template');

        return $this->render('organization/report_template/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_report_template'
        ]);
    }

    /**
     * @Route("/eliminar", name="organization_report_template_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        ReportTemplateRepository $reportTemplateRepository,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_report_template_list');
        }

        $templates = $reportTemplateRepository->findAllInListByIdAndOrganization($items, $organization);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $reportTemplateRepository->deleteFromList($templates);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_report_template'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_report_template'));
            }
            return $this->redirectToRoute('organization_report_template_list');
        }

        return $this->render('organization/report_template/delete.html.twig', [
            'menu_path' => 'organization_report_template_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_report_template')]],
            'title' => $translator->trans('title.delete', [], 'edu_report_template'),
            'items' => $templates
        ]);
    }
}
