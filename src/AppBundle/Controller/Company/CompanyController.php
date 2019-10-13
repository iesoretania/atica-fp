<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Controller\Company;

use AppBundle\Entity\Company;
use AppBundle\Entity\Workcenter;
use AppBundle\Form\Type\CompanyType;
use AppBundle\Repository\CompanyRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/empresa")
 */
class CompanyController extends Controller
{
    /**
     * @Route("/nueva", name="company_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService
    ) {
        $company = new Company();

        $this->getDoctrine()->getManager()->persist($company);

        return $this->formAction($request, $translator, $userExtensionService, $company);
    }

    /**
     * @Route("/{id}", name="company_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function formAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Company $company
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $form = $this->createForm(CompanyType::class, $company);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($company->getId() == 0) {
                    $workcenter = new Workcenter();
                    $workcenter
                        ->initFromCompany($company)
                        ->setName($translator->trans('title.main_workcenter', [], 'workcenter'));

                    $this->getDoctrine()->getManager()->persist($workcenter);
                }

                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'company'));
                return $this->redirectToRoute('company');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'company'));
            }
        }

        $title = $translator->trans(
            $company->getId() ? 'title.edit' : 'title.new',
            [],
            'company'
        );

        $breadcrumb = [
            $company->getId() ?
                ['fixed' => $company->getName()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'company')]
        ];

        return $this->render('company/company_form.html.twig', [
            'menu_path' => 'company',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'company' => $company,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{page}", name="company", requirements={"page" = "\d+"},
     *     defaults={"page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('c')
            ->from(Company::class, 'c')
            ->orderBy('c.name')
            ->addOrderBy('c.city');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('c.code LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('c.city LIKE :tq')
                ->orWhere('c.emailAddress LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'company');

        return $this->render('company/company_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'company'
        ]);
    }

    /**
     * @Route("/eliminar", name="company_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        CompanyRepository $companyRepository,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService)
    {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('company');
        }

        $companies = $companyRepository->findAllInListById($items);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $companyRepository->deleteFromList($companies);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'company'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'company'));
            }
            return $this->redirectToRoute('company');
        }

        $breadcrumb = [
            ['fixed' => $this->get('translator')->trans('title.delete', [], 'company')]
        ];

        return $this->render('company/company_delete.html.twig', [
            'menu_path' => 'company',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'company'),
            'items' => $companies
        ]);
    }
}
