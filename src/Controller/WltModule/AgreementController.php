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

namespace App\Controller\WltModule;

use App\Entity\Person;
use App\Entity\WltModule\Agreement;
use App\Entity\WltModule\AgreementActivityRealization;
use App\Entity\WltModule\Project;
use App\Form\Model\WltModule\CalendarCopy;
use App\Form\Type\WltModule\AgreementType;
use App\Form\Type\WltModule\CalendarCopyType;
use App\Repository\WltModule\AgreementActivityRealizationRepository;
use App\Repository\WltModule\AgreementRepository;
use App\Security\WltModule\AgreementVoter;
use App\Security\WltModule\OrganizationVoter;
use App\Security\WltModule\ProjectVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/dual/acuerdo')]
class AgreementController extends AbstractController
{
    #[Route(path: '/nuevo/{project}', name: 'work_linked_training_agreement_new', requirements: ['project' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementActivityRealizationRepository $agreementActivityRealizationRepository,
        ManagerRegistry $managerRegistry,
        Project $project
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::WLT_MANAGE, $organization);

        $agreement = new Agreement();
        $agreement
            ->setProject($project);

        $managerRegistry->getManager()->persist($agreement);

        return $this->index(
            $request,
            $userExtensionService,
            $translator,
            $agreementActivityRealizationRepository,
            $managerRegistry,
            $agreement
        );
    }

    #[Route(path: '/{id}', name: 'work_linked_training_agreement_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementActivityRealizationRepository $agreementActivityRealizationRepository,
        ManagerRegistry $managerRegistry,
        Agreement $agreement
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::WLT_MANAGE, $organization);
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);
        $readOnly = !$this->isGranted(AgreementVoter::MANAGE, $agreement);

        $em = $managerRegistry->getManager();

        $form = $this->createForm(AgreementType::class, $agreement, [
            'disabled' => $readOnly
        ]);

        $oldActivityRealizations = $agreement->getActivityRealizations();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // actualizar concreciones del convenio
                $currentActivityRealizations = $form->get('activityRealizations')->getData();

                $toInsert = array_diff($currentActivityRealizations->toArray(), $oldActivityRealizations->toArray());
                foreach ($toInsert as $activityRealization) {
                    $agreementActivityRealization = new AgreementActivityRealization();
                    $agreementActivityRealization
                        ->setDisabled(false)
                        ->setAgreement($agreement)
                        ->setActivityRealization($activityRealization);
                    $managerRegistry->getManager()->persist($agreementActivityRealization);
                }

                if ($agreement->getId() !== null) {
                    $toRemove = array_diff(
                        $oldActivityRealizations->toArray(),
                        $currentActivityRealizations->toArray()
                    );
                    $agreementActivityRealizationRepository->deleteFromList($agreement, $toRemove);
                }

                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_agreement'));
                return $this->redirectToRoute('work_linked_training_agreement_list', [
                    'id' => $agreement->getProject()->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_agreement'));
            }
        }

        $title = $translator->trans(
            $agreement->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wlt_agreement'
        );

        $breadcrumb = [
            [
                'fixed' => $agreement->getProject()->getName(),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.agreements', [], 'wlt_project'),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            $agreement->getId() !== null ?
                ['fixed' => (string) $agreement] :
                ['fixed' => $translator->trans('title.new', [], 'wlt_agreement')]
        ];

        return $this->render('wlt/agreement/form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'agreement' => $agreement,
            'read_only' => $readOnly
        ]);
    }

    #[Route(path: '/{id}/listar/{page}', name: 'work_linked_training_agreement_list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        ManagerRegistry $managerRegistry,
        Project $project,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        if ($project->getOrganization() !== $organization) {
            throw $this->createAccessDeniedException();
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->addSelect('gr')
            ->addSelect('t')
            ->addSelect('wt')
            ->addSelect('et')
            ->addSelect('etp')
            ->from(Agreement::class, 'a')
            ->join('a.workcenter', 'w')
            ->join('a.educationalTutor', 'et')
            ->join('et.person', 'etp')
            ->leftJoin('a.additionalEducationalTutor', 'aet')
            ->leftJoin('aet.person', 'aetp')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->leftJoin('a.additionalWorkTutor', 'awt')
            ->join('a.project', 'pro')
            ->orderBy('g.name')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('etp.lastName LIKE :tq')
                ->orWhere('etp.firstName LIKE :tq')
                ->orWhere('aetp.lastName LIKE :tq')
                ->orWhere('aetp.firstName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->orWhere('wt.firstName LIKE :tq')
                ->orWhere('wt.lastName LIKE :tq')
                ->orWhere('wt.uniqueIdentifier LIKE :tq')
                ->orWhere('awt.firstName LIKE :tq')
                ->orWhere('awt.lastName LIKE :tq')
                ->orWhere('awt.uniqueIdentifier LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        /** @var Person $person */
        $person = $this->getUser();

        $projects = $agreementRepository->setQueryBuilderFilterByOrganizationManagerPersonProjectAndReturnProjects(
            $queryBuilder,
            $organization,
            $person,
            $project
        );

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'wlt_agreement');

        $breadcrumb = [
            ['fixed' => $project->getName()],
            ['fixed' => $translator->trans('title.agreements', [], 'wlt_project')]
        ];

        return $this->render('wlt/agreement/list.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_agreement',
            'project' => $project,
            'projects' => $projects
        ]);
    }

    #[Route(path: '/operacion/{project}', name: 'work_linked_training_agreement_operation', requirements: ['project' => '\d+'], methods: ['POST'])]
    public function operation(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        ManagerRegistry $managerRegistry,
        Project $project
    ): Response|RedirectResponse {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::WLT_MANAGE, $organization);

        $items = $request->request->all('items');

        if (count($items) !== 0) {
            if ('' === $request->get('delete')) {
                return $this->delete($items, $request, $translator, $agreementRepository, $managerRegistry, $project);
            }
            if ('' === $request->get('copy')) {
                return $this->copy($items, $request, $translator, $agreementRepository, $managerRegistry, $project);
            }
        }

        return $this->redirectToRoute(
            'work_linked_training_agreement_list',
            ['id' => $project->getId()]
        );
    }

    private function delete(
        $items,
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        ManagerRegistry $managerRegistry,
        Project $project
    ): Response {
        $em = $managerRegistry->getManager();

        $agreements = $agreementRepository->findAllInListByIdAndProject($items, $project);

        // comprobar individualmente que tenemos acceso
        foreach ($agreements as $agreement) {
            $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                $agreementRepository->deleteFromList($agreements);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_agreement'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_agreement'));
            }
            return $this->redirectToRoute(
                'work_linked_training_agreement_list',
                ['id' => $project->getId()]
            );
        }
        $breadcrumb = [
            [
                'fixed' => $project->getName(),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $project->getId()]
            ],
            ['fixed' => $translator->trans('title.delete', [], 'wlt_agreement')]
        ];
        return $this->render('wlt/agreement/delete.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'wlt_agreement'),
            'items' => $agreements
        ]);
    }

    private function copy(
        $items,
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        ManagerRegistry $managerRegistry,
        Project $project
    ): Response {
        $agreement = null;
        $em = $managerRegistry->getManager();

        $selectedAgreements = $agreementRepository->findAllInListByIdAndProject($items, $project);
        // comprobar individualmente que tenemos acceso
        foreach ($selectedAgreements as $agreement) {
            $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);
        }
        $agreementChoices = $agreementRepository->findAllInListByNotIdAndProject($items, $project);
        $calendarCopy = new CalendarCopy();

        $form = $this->createForm(CalendarCopyType::class, $calendarCopy, [
            'agreements' => $agreementChoices
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                foreach ($selectedAgreements as $agreement) {
                    $agreementRepository->cloneCalendarFromAgreement(
                        $agreement,
                        $calendarCopy->getAgreement(),
                        $calendarCopy->getOverwriteAction() === CalendarCopy::OVERWRITE_ACTION_REPLACE
                    );
                }
                $em->flush();
                foreach ($selectedAgreements as $agreement) {
                    $agreementRepository->updateDates($agreement);
                }
                $this->addFlash('success', $translator->trans('message.copied', [], 'wlt_agreement'));
                return $this->redirectToRoute('work_linked_training_agreement_list', [
                    'id' => $project->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.copy_error', [], 'wlt_agreement'));
            }
        }

        $title = $translator->trans('title.copy', [], 'wlt_agreement');
        $breadcrumb = [
            [
                'fixed' => $agreement->getProject()->getName(),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            ['fixed' => $title]
        ];
        return $this->render('wlt/agreement/copy.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'items' => $selectedAgreements
        ]);
    }
}
