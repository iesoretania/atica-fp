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

namespace App\Controller\Company;

use App\Entity\Company;
use App\Entity\Workcenter;
use App\Form\Type\CompanyType;
use App\Repository\CompanyRepository;
use App\Repository\PersonRepository;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/empresa")
 */
class CompanyController extends AbstractController
{
    /**
     * @Route("/nueva", name="company_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry
    ): Response {
        $company = new Company();

        $managerRegistry->getManager()->persist($company);

        return $this->formAction($request, $translator, $userExtensionService, $managerRegistry, $company);
    }

    /**
     * @Route("/{id}", name="company_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function formAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        Company $company
    ): Response {
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

                    $managerRegistry->getManager()->persist($workcenter);
                }

                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'company'));
                return $this->redirectToRoute('company');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'company'));
            }
        }

        $title = $translator->trans(
            $company->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'company'
        );

        $breadcrumb = [
            $company->getId() !== null ?
                ['fixed' => $company->getName()] :
                ['fixed' => $translator->trans('title.new', [], 'company')]
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
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('c')
            ->from(Company::class, 'c')
            ->orderBy('c.name')
            ->addOrderBy('c.city');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('c.code LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('c.city LIKE :tq')
                ->orWhere('c.emailAddress LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'company');

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
        ManagerRegistry $managerRegistry,
        UserExtensionService $userExtensionService
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
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
            ['fixed' => $translator->trans('title.delete', [], 'company')]
        ];

        return $this->render('company/company_delete.html.twig', [
            'menu_path' => 'company',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'company'),
            'items' => $companies
        ]);
    }

    /**
     * @Route("/api/person/query", name="api_person_query", methods={"GET"})
     */
    public function apiPersonQuery(
        Request $request,
        UserExtensionService $userExtensionService,
        PersonRepository $personRepository,
        TranslatorInterface $translator
    ): Response {
        $term = $request->get('q');

        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $persons = $term === null ? [] : [$personRepository->findOneByUniqueIdentifierOrUsernameOrEmailAddress($term)];

        if (count($persons) === 0 || null === $persons[0]) {
            $data = [
                ['id' => 0, 'term' => $term, 'text' => $translator->trans('title.new', [], 'person')]
            ];
        } else {
            $data = [];
            foreach ($persons as $person) {
                $data[] = ['id' => $person->getId(), 'term' => $term, 'text' => $person->getFullDisplayname()];
            }
        }

        return new JsonResponse($data);
    }
}
