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

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Training;
use App\Form\Type\Edu\TrainingType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TrainingRepository;
use App\Security\Edu\AcademicYearVoter;
use App\Security\OrganizationVoter;
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

#[Route(path: '/centro/ensenanza')]
class TrainingController extends AbstractController
{
    #[Route(path: '/nuevo', name: 'organization_training_new', methods: ['GET', 'POST'])]
    #[Route(path: '/{id}', name: 'organization_training_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Training $training = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $managerRegistry->getManager();

        if (!$training instanceof Training) {
            $training = new Training();
            $training
                ->setAcademicYear($organization->getCurrentAcademicYear());
            $em->persist($training);
        } else {
            $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $training->getAcademicYear());
        }

        $form = $this->createForm(TrainingType::class, $training, [
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_training'));
                return $this->redirectToRoute('organization_training_list', [
                    'academicYear' => $training->getAcademicYear()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_training'));
            }
        }

        $title = $translator->trans(
            $training->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_training'
        );

        $breadcrumb = [
            $training->getId() !== null ?
                ['fixed' => $training->getName()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_training')]
        ];

        return $this->render('organization/training/form.html.twig', [
            'menu_path' => 'organization_training_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{academicYear}/{page}', name: 'organization_training_list', requirements: ['page' => '\d+'], defaults: ['academicYear' => null, 'page' => 1], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
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
            ->select('t')
            ->from(Training::class, 't')
            ->orderBy('t.name');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('t.name LIKE :tq')
                ->orWhere('t.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
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

        $title = $translator->trans('title.list', [], 'edu_training');

        return $this->render('organization/training/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_training',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/eliminar/{academicYear}', name: 'organization_training_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        TrainingRepository $trainingRepository,
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
            return $this->redirectToRoute('organization_training_list', ['academicYear' => $academicYear->getId()]);
        }

        $trainings = $trainingRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $em->createQueryBuilder()
                    ->delete(Training::class, 't')
                    ->where('t IN (:items)')
                    ->setParameter('items', $trainings)
                    ->getQuery()
                    ->execute();

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_training'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_training'));
            }
            return $this->redirectToRoute('organization_training_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/training/delete.html.twig', [
            'menu_path' => 'organization_training_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_training')]],
            'title' => $translator->trans('title.delete', [], 'edu_training'),
            'trainings' => $trainings
        ]);
    }
}
