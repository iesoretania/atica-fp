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

namespace App\Controller\WLT;

use App\Entity\Person;
use App\Entity\WLT\Agreement;
use App\Entity\WLT\AgreementActivityRealization;
use App\Entity\WLT\Project;
use App\Form\Model\WLT\CalendarCopy;
use App\Form\Type\WLT\AgreementType;
use App\Form\Type\WLT\CalendarCopyType;
use App\Repository\WLT\AgreementActivityRealizationRepository;
use App\Repository\WLT\AgreementRepository;
use App\Security\WLT\AgreementVoter;
use App\Security\WLT\ProjectVoter;
use App\Security\WLT\WLTOrganizationVoter;
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
 * @Route("/dual/acuerdo")
 */
class AgreementController extends AbstractController
{
    /**
     * @Route("/nuevo/{project}", name="work_linked_training_agreement_new",
     *     requirements={"project": "\d+"}, methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementActivityRealizationRepository $agreementActivityRealizationRepository,
        Project $project
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $agreement = new Agreement();
        $agreement
            ->setProject($project);

        $this->getDoctrine()->getManager()->persist($agreement);

        return $this->indexAction(
            $request,
            $userExtensionService,
            $translator,
            $agreementActivityRealizationRepository,
            $agreement
        );
    }

    /**
     * @Route("/{id}", name="work_linked_training_agreement_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementActivityRealizationRepository $agreementActivityRealizationRepository,
        Agreement $agreement
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);
        $readOnly = !$this->isGranted(AgreementVoter::MANAGE, $agreement);

        $oldWorkTutor = $agreement->getWorkTutor();

        if (null === $agreement->getStudentEnrollment()) {
            $academicYear = $organization->getCurrentAcademicYear();
        } else {
            $academicYear = $agreement->
                getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();
        }

        $em = $this->getDoctrine()->getManager();

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
                        ->setAgreement($agreement)
                        ->setActivityRealization($activityRealization);
                    $this->getDoctrine()->getManager()->persist($agreementActivityRealization);
                }

                if ($agreement->getId() !== 0) {
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
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_agreement'));
            }
        }

        $title = $translator->trans(
            $agreement->getId() !== 0 ? 'title.edit' : 'title.new',
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
            $agreement->getId() !== 0 ?
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

    /**
     * @Route("/{id}/listar/{page}", name="work_linked_training_agreement_list",
     *     requirements={"page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        Project $project,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        if ($project) {
            $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);
        } else {
            $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);
        }

        if ($project && $project->getOrganization() !== $organization) {
            throw $this->createAccessDeniedException();
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

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
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->join('a.project', 'pro')
            ->orderBy('g.name')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->orWhere('wt.firstName LIKE :tq')
                ->orWhere('wt.lastName LIKE :tq')
                ->orWhere('wt.uniqueIdentifier LIKE :tq')
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
        } catch (OutOfRangeCurrentPageException $e) {
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

    /**
     * @Route("/operacion/{project}", name="work_linked_training_agreement_operation",
     *     requirements={"project": "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        Project $project
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $items = $request->request->get('items', []);

        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) !== 0) {
            if ('' === $request->get('delete')) {
                return $this->deleteAction($items, $request, $translator, $agreementRepository, $project);
            }
            if ('' === $request->get('copy')) {
                return $this->copyAction($items, $request, $translator, $agreementRepository, $project);
            }
        }

        return $this->redirectToRoute(
            'work_linked_training_agreement_list',
            ['id' => $project->getId()]
        );
    }

    private function deleteAction(
        $items,
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        Project $project
    ) {
        $em = $this->getDoctrine()->getManager();

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
            } catch (\Exception $e) {
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

    private function copyAction(
        $items,
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        Project $project
    ) {
        $agreement = null;
        $em = $this->getDoctrine()->getManager();

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
            } catch (\Exception $e) {
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
