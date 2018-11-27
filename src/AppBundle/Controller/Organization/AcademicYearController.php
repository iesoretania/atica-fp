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
use AppBundle\Entity\Organization;
use AppBundle\Form\Type\Edu\AcademicYearType;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/centro/cursoacademico")
 */
class AcademicYearController extends Controller
{
    /**
     * @Route("/nuevo", name="organization_academic_year_new", methods={"GET", "POST"})
     * @Route("/{id}", name="organization_academic_year_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(Request $request, UserExtensionService $userExtensionService, AcademicYear $academicYear = null)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $academicYear) {
            $academicYear = new AcademicYear();
            $academicYear
                ->setOrganization($organization);
            $em->persist($academicYear);
        }

        $form = $this->createForm(AcademicYearType::class, $academicYear, [
            'academic_year' => $academicYear
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'edu_academic_year'));
                return $this->redirectToRoute('organization_academic_year_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.error', [], 'edu_academic_year'));
            }
        }

        $title = $this->get('translator')->trans(
            $academicYear->getId() ? 'title.edit' : 'title.new',
            [],
            'edu_academic_year'
        );

        $breadcrumb = [
            $academicYear->getId() ?
                ['fixed' => (string) $academicYear] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'edu_academic_year')]
        ];

        return $this->render('organization/academic_year/form.html.twig', [
            'menu_path' => 'organization_academic_year_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{page}", name="organization_academic_year_list", requirements={"page" = "\d+"},
     *     defaults={"page" = 1},   methods={"GET"})
     */
    public function listAction(Request $request, UserExtensionService $userExtensionService, $page = 1)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('ay')
            ->from(AcademicYear::class, 'ay')
            ->leftJoin('ay.financialManager', 'fm')
            ->leftJoin('ay.principal', 'p')
            ->orderBy('ay.description', 'DESC');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->andWhere('ay.description LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('fm.firstName LIKE :tq')
                ->orWhere('fm.lastName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%')
                ->setParameter('q', $q);
        }

        $queryBuilder
            ->andWhere('ay.organization = :organization')
            ->setParameter('organization', $organization);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'edu_academic_year');

        return $this->render('organization/academic_year/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'current' => $organization->getCurrentAcademicYear(),
            'domain' => 'edu_academic_year'
        ]);
    }

    /**
     * @Route("/operacion", name="organization_academic_year_operation", methods={"POST"})
     */
    public function operationAction(Request $request, UserExtensionService $userExtensionService)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        list($redirect, $academicYears) = $this->processOperations(
            $request,
            $userExtensionService->getCurrentOrganization(),
            $organization->getCurrentAcademicYear()
        );

        if ($redirect) {
            return $this->redirectToRoute('organization_academic_year_list');
        }

        return $this->render('organization/academic_year/delete.html.twig', [
            'menu_path' => 'organization_academic_year_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'edu_academic_year')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'edu_academic_year'),
            'academic_years' => $academicYears
        ]);
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param AcademicYear $current
     * @return array
     */
    private function processOperations(
        Request $request,
        Organization $organization,
        AcademicYear $current
    ) {
        $em = $this->getDoctrine()->getManager();

        $redirect = false;
        if ($request->request->has('switch')) {
            $redirect = $this->processSwitchAcademicYear($request, $organization, $em);
        }

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            $redirect = true;
        }

        $academicYears = [];
        if (!$redirect) {
            $academicYears = $em->getRepository(AcademicYear::class)->
                findAllInListByIdAndOrganizationButCurrent($items, $organization, $current);
            $redirect = $this->processRemoveAcademicYear($request, $academicYears, $em);
        }
        return array($redirect, $academicYears);
    }


    /**
     * @param Request $request
     * @param array $academicYears
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     * @return bool
     */
    private function processRemoveAcademicYear(Request $request, $academicYears, $em)
    {
        $redirect = false;
        if ($request->get('confirm', '') === 'ok') {
            try {
                $this->deleteAcademicYears($academicYears);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'edu_academic_year'));
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $this->get('translator')->trans('message.delete_error', [], 'edu_academic_year')
                );
            }
            $redirect = true;
        }
        return $redirect;
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     * @return bool
     */
    private function processSwitchAcademicYear(Request $request, Organization $organization, $em)
    {
        $academicYear = $em->getRepository(AcademicYear::class)->findOneBy(
            [
                'id' => $request->request->get('switch', null),
                'organization' => $organization
            ]
        );
        if ($academicYear) {
            $organization->setCurrentAcademicYear($academicYear);
            $em->flush();

            $this->addFlash('success', $this->get('translator')->
                trans('message.switched', ['%name%' => $academicYear->getDescription()], 'edu_academic_year'));
            return true;
        }
        return false;
    }

    /**
     * Borrar los datos de las organizaciones pasados como parámetros
     *
     * @param AcademicYear[] $academicYears
     */
    private function deleteAcademicYears($academicYears)
    {
        $em = $this->getDoctrine()->getManager();

        /* Borrar cursos académicos */
        $em->createQueryBuilder()
            ->delete(AcademicYear::class, 'ay')
            ->where('ay IN (:items)')
            ->setParameter('items', $academicYears)
            ->getQuery()
            ->execute();
    }
}
