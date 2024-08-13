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
use App\Entity\Organization;
use App\Form\Model\Edu\AcademicYearCopy;
use App\Form\Type\Edu\AcademicYearCopyType;
use App\Form\Type\Edu\AcademicYearType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TrainingRepository;
use App\Security\Edu\AcademicYearVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/centro/cursoacademico')]
class AcademicYearController extends AbstractController
{
    #[Route(path: '/nuevo', name: 'organization_academic_year_new', methods: ['GET', 'POST'])]
    #[Route(path: '/{id}', name: 'organization_academic_year_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $managerRegistry->getManager();

        if (null === $academicYear) {
            $academicYear = new AcademicYear();
            $year = date('Y');
            $startDate = new \DateTime($year . '/09/01');
            $endDate = new \DateTime(($year + 1) . '/08/31');
            $academicYear
                ->setOrganization($organization)
                ->setStartDate($startDate)
                ->setEndDate($endDate);
            $em->persist($academicYear);
        }

        $form = $this->createForm(AcademicYearType::class, $academicYear, [
            'academic_year' => $academicYear->getId() !== null ? $academicYear : null
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_academic_year'));
                return $this->redirectToRoute('organization_academic_year_list');
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_academic_year'));
            }
        }

        $title = $translator->trans(
            $academicYear->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_academic_year'
        );

        $breadcrumb = [
            $academicYear->getId() !== null ?
                ['fixed' => (string) $academicYear] :
                ['fixed' => $translator->trans('title.new', [], 'edu_academic_year')]
        ];

        return $this->render('organization/academic_year/form.html.twig', [
            'menu_path' => 'organization_academic_year_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{page}', name: 'organization_academic_year_list', requirements: ['page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function list(
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
            ->select('ay')
            ->from(AcademicYear::class, 'ay')
            ->leftJoin('ay.financialManager', 'fm')
            ->leftJoin('fm.person', 'fmp')
            ->leftJoin('ay.principal', 'p')
            ->leftJoin('p.person', 'pp')
            ->orderBy('ay.description', 'DESC');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->andWhere('ay.description LIKE :tq')
                ->orWhere('pp.firstName LIKE :tq')
                ->orWhere('pp.lastName LIKE :tq')
                ->orWhere('fmp.firstName LIKE :tq')
                ->orWhere('fmp.lastName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('ay.organization = :organization')
            ->setParameter('organization', $organization);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'edu_academic_year');

        return $this->render('organization/academic_year/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'current' => $organization->getCurrentAcademicYear(),
            'domain' => 'edu_academic_year'
        ]);
    }

    #[Route(path: '/operacion', name: 'organization_academic_year_operation', methods: ['POST'])]
    public function operation(
        Request $request,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        AcademicYearRepository $academicYearRepository,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        [$redirect, $academicYears] = $this->processOperations(
            $request,
            $translator,
            $managerRegistry,
            $academicYearRepository,
            $userExtensionService->getCurrentOrganization(),
            $organization->getCurrentAcademicYear()
        );

        if ($redirect) {
            return $this->redirectToRoute('organization_academic_year_list');
        }

        return $this->render('organization/academic_year/delete.html.twig', [
            'menu_path' => 'organization_academic_year_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_academic_year')]],
            'title' => $translator->trans('title.delete', [], 'edu_academic_year'),
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
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AcademicYearRepository $academicYearRepository,
        Organization $organization,
        AcademicYear $current
    ) {
        $em = $managerRegistry->getManager();

        $redirect = false;
        if ($request->request->has('switch')) {
            $redirect = $this->processSwitchAcademicYear($request, $translator, $academicYearRepository, $organization, $em);
        }

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            $redirect = true;
        }

        $academicYears = [];
        if (!$redirect) {
            $academicYears = $academicYearRepository->
                findAllInListByIdAndOrganizationButCurrent($items, $organization, $current);
            $redirect = $this->processRemoveAcademicYear($request, $academicYears, $em, $translator);
        }
        return [$redirect, $academicYears];
    }


    /**
     * @param Request $request
     * @param array $academicYears
     * @param ObjectManager $em
     * @return bool
     */
    private function processRemoveAcademicYear(Request $request, $academicYears, $em, TranslatorInterface $translator)
    {
        $redirect = false;
        if ($request->get('confirm', '') === 'ok') {
            try {
                $this->deleteAcademicYears($academicYears, $em);
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_academic_year'));
            } catch (\Exception) {
                $this->addFlash(
                    'error',
                    $translator->trans('message.delete_error', [], 'edu_academic_year')
                );
            }
            $redirect = true;
        }
        return $redirect;
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param ObjectManager $em
     * @return bool
     */
    private function processSwitchAcademicYear(
        Request $request,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        Organization $organization,
        $em
    ) {
        $academicYear = $academicYearRepository->findOneBy(
            [
                'id' => $request->request->get('switch'),
                'organization' => $organization
            ]
        );
        if ($academicYear) {
            $organization->setCurrentAcademicYear($academicYear);
            $em->flush();

            $this->addFlash('success', $translator->
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
    private function deleteAcademicYears($academicYears, ObjectManager $em)
    {
        /* Borrar cursos académicos */
        $em->createQueryBuilder()
            ->delete(AcademicYear::class, 'ay')
            ->where('ay IN (:items)')
            ->setParameter('items', $academicYears)
            ->getQuery()
            ->execute();
    }

    #[Route(path: '/copiar/{id}', name: 'organization_academic_year_copy', methods: ['GET', 'POST'])]
    public function copy(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        TrainingRepository $trainingRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        $academicYears = $academicYearRepository->findAllByOrganizationButOne($organization, $academicYear);

        $academicYearCopy = new AcademicYearCopy();
        $form = $this->createForm(AcademicYearCopyType::class, $academicYearCopy, [
            'academic_years' => $academicYears
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // copiar datos del curso académico
            try {
                $trainingRepository->copyFromAcademicYear(
                    $academicYear,
                    $academicYearCopy->getAcademicYear()
                );

                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.copied', [], 'edu_academic_year'));

                return $this->redirectToRoute('organization_academic_year_list');
            } catch (\Exception) {
                $this->addFlash(
                    'error',
                    $translator->trans('message.copy_error', [], 'edu_academic_year')
                );
            }
        }

        $title = $translator->trans('title.copy', [], 'edu_academic_year');

        return $this->render('organization/academic_year/copy.html.twig', [
            'menu_path' => 'organization_academic_year_list',
            'breadcrumb' => [['fixed' => $title]],
            'title' => $title,
            'form' => $form->createView(),
            'academic_year' => $academicYear,
            'academic_years' => $academicYears
        ]);
    }
}
