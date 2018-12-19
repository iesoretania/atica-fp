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

namespace AppBundle\Controller\WLT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\WLT\LearningProgram;
use AppBundle\Form\Type\WLT\LearningProgramType;
use AppBundle\Repository\WLT\LearningProgramRepository;
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
 * @Route("/dual/programa")
 */
class LearningProgramController extends Controller
{
    /**
     * @Route("/nuevo", name="work_linked_training_learning_program_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_WORK_LINKED_TRAINING, $organization);

        $learningProgram = new LearningProgram();
        $this->getDoctrine()->getManager()->persist($learningProgram);

        return $this->indexAction($request, $userExtensionService, $translator, $learningProgram);
    }

    /**
     * @Route("/{id}", name="work_linked_training_learning_program_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        LearningProgram $learningProgram
    ) {
        if ($learningProgram->getTraining()) {
            $academicYear = $learningProgram->getTraining()->getAcademicYear();
        } else {
            $academicYear = $userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(
            OrganizationVoter::MANAGE_WORK_LINKED_TRAINING,
            $academicYear->getOrganization()
        );

        if (false === $userExtensionService->isUserLocalAdministrator() &&
            $academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            return $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(LearningProgramType::class, $learningProgram);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_learning_program'));
                return $this->redirectToRoute('work_linked_training_learning_program_list', [
                    'academicYear' => $academicYear
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_learning_program'));
            }
        }

        $title = $translator->trans(
            $learningProgram->getId() ? 'title.edit' : 'title.new',
            [],
            'wlt_learning_program'
        );

        $breadcrumb = [
            $learningProgram->getId() ?
                ['fixed' => (string) $learningProgram] :
                ['fixed' => $translator->trans('title.new', [], 'wlt_learning_program')]
        ];

        return $this->render('wlt/learning_program/form.html.twig', [
            'menu_path' => 'work_linked_training_learning_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="work_linked_training_learning_program_list",
     *     requirements={"page" = "\d+"}, defaults={"academicYear" = null, "page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_WORK_LINKED_TRAINING, $organization);
        if (false === $userExtensionService->isUserLocalAdministrator() &&
            $academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            return $this->createAccessDeniedException();
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('cp')
            ->addSelect('SIZE(cp.activityRealizations)')
            ->from(LearningProgram::class, 'cp')
            ->join('cp.company', 'c')
            ->join('cp.training', 't')
            ->addOrderBy('c.name')
            ->addOrderBy('t.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('c.name LIKE :tq')
                ->orWhere('c.code LIKE :tq')
                ->orWhere('cp.name LIKE :tq')
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

        $title = $translator->trans('title.list', [], 'wlt_learning_program');

        return $this->render('wlt/learning_program/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_learning_program',
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/operacion/{academicYear}", name="work_linked_training_learning_program_operation", methods={"POST"})
     */
    public function operationAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        LearningProgramRepository $learningProgramRepository,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(
            OrganizationVoter::MANAGE_WORK_LINKED_TRAINING,
            $academicYear->getOrganization()
        );

        if (false === $userExtensionService->isUserLocalAdministrator() &&
            $academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            return $this->createAccessDeniedException();
        }

        $items = $request->request->get('items', []);

        if (count($items) !== 0) {
            if ('' === $request->get('delete')) {
                return $this->deleteAction($items, $request, $translator, $learningProgramRepository, $academicYear);
            }
        }

        return $this->redirectToRoute(
            'work_linked_training_learning_program_list',
            ['academicYear' => $academicYear->getId()]
        );
    }

    private function deleteAction(
        $items,
        Request $request,
        TranslatorInterface $translator,
        LearningProgramRepository $learningProgramRepository,
        AcademicYear $academicYear
    ) {
        $em = $this->getDoctrine()->getManager();


        $learningPrograms = $learningProgramRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $learningProgramRepository->deleteFromList($learningPrograms);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_learning_program'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_learning_program'));
            }
            return $this->redirectToRoute(
                'work_linked_training_learning_program_list',
                ['academicYear' => $academicYear->getId()]
            );
        }

        return $this->render('wlt/learning_program/delete.html.twig', [
            'menu_path' => 'work_linked_training_agreement_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'wlt_learning_program')]],
            'title' => $translator->trans('title.delete', [], 'wlt_learning_program'),
            'items' => $learningPrograms
        ]);
    }
}
