<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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
use App\Entity\Edu\ContactMethod;
use App\Form\Type\Edu\ContactMethodType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\ContactMethodRepository;
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
 * @Route("/centro/contactos")
 */
class ContactMethodController extends AbstractController
{
    /**
     * @Route("/nuevo", name="organization_contact_method_new", methods={"GET", "POST"})
     * @Route("/{id}", name="organization_contact_method_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ContactMethod $contactMethod = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $contactMethod) {
            $contactMethod = new ContactMethod();
            $contactMethod
                ->setAcademicYear($organization->getCurrentAcademicYear());
            $em->persist($contactMethod);
        }

        $form = $this->createForm(ContactMethodType::class, $contactMethod, [
            'academic_year' => $contactMethod->getAcademicYear()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_contact_method'));
                return $this->redirectToRoute('organization_contact_method_list', [
                    'academicYear' => $contactMethod->getAcademicYear()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_contact_method'));
            }
        }

        $title = $translator->trans(
            $contactMethod->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_contact_method'
        );

        $breadcrumb = [
            $contactMethod->getId() !== null ?
                ['fixed' => $contactMethod->getDescription()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_contact_method')]
        ];

        return $this->render('organization/contact_method/form.html.twig', [
            'menu_path' => 'organization_contact_method_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'contact_method' => $contactMethod,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="organization_contact_method_list", requirements={"page" = "\d+"},
     *     defaults={"academicYear" = null, "page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
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
            ->select('cm')
            ->from(ContactMethod::class, 'cm')
            ->orderBy('cm.description');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('cm.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('cm.academicYear = :academic_year')
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

        $title = $translator->trans('title.list', [], 'edu_contact_method');

        return $this->render('organization/contact_method/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_contact_method',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/eliminar/{academicYear}", name="organization_contact_method_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        ContactMethodRepository $contactMethodRepository,
        TranslatorInterface $translator,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_contact_method_list', ['academicYear' => $academicYear->getId()]);
        }

        $contactMethods = $contactMethodRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $contactMethodRepository->deleteFromList($contactMethods);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_contact_method'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_contact_method'));
            }
            return $this->redirectToRoute('organization_contact_method_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/contact_method/delete.html.twig', [
            'menu_path' => 'organization_contact_method_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_contact_method')]],
            'title' => $translator->trans('title.delete', [], 'edu_contact_method'),
            'items' => $contactMethods
        ]);
    }
}
