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
use AppBundle\Entity\Edu\Training;
use AppBundle\Form\Type\Edu\TrainingType;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\TrainingRepository;
use AppBundle\Security\Edu\AcademicYearVoter;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/centro/ensenanza")
 */
class TrainingController extends Controller
{
    /**
     * @Route("/nuevo", name="organization_training_new", methods={"GET", "POST"})
     * @Route("/{id}", name="organization_training_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        Training $training = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $training) {
            $training = new Training();
            $training
                ->setAcademicYear($organization->getCurrentAcademicYear());
            $em->persist($training);
        } else {
            $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $training->getAcademicYear());
        }

        $form = $this->createForm(TrainingType::class, $training);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'edu_training'));
                return $this->redirectToRoute('organization_training_list', [
                    'academicYear' => $training->getAcademicYear()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.error', [], 'edu_training'));
            }
        }

        $title = $this->get('translator')->trans(
            $training->getId() ? 'title.edit' : 'title.new',
            [],
            'edu_training'
        );

        $breadcrumb = [
            $training->getId() ?
                ['fixed' => $training->getName()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'edu_training')]
        ];

        return $this->render('organization/training/form.html.twig', [
            'menu_path' => 'organization_training_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="organization_training_list", requirements={"page" = "\d+"},
     *     defaults={"academicYear" = null, "page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
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
            ->select('t')
            ->from(Training::class, 't')
            ->orderBy('t.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('t.name LIKE :tq')
                ->orWhere('t.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'edu_training');

        return $this->render('organization/training/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_training',
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/eliminar/{academicYear}", name="organization_training_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TrainingRepository $trainingRepository,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        if ($academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            return $this->createNotFoundException();
        }

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
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
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'edu_training'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'edu_training'));
            }
            return $this->redirectToRoute('organization_training_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/training/delete.html.twig', [
            'menu_path' => 'organization_training_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'edu_training')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'edu_training'),
            'trainings' => $trainings
        ]);
    }
}
