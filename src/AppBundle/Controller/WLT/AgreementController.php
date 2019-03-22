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

namespace AppBundle\Controller\WLT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\AgreementActivityRealization;
use AppBundle\Form\Model\CalendarCopy;
use AppBundle\Form\Type\WLT\AgreementType;
use AppBundle\Form\Type\WLT\CalendarCopyType;
use AppBundle\Repository\MembershipRepository;
use AppBundle\Repository\WLT\AgreementActivityRealizationRepository;
use AppBundle\Repository\WLT\AgreementRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WLT\AgreementVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/acuerdo")
 */
class AgreementController extends Controller
{
    /**
     * @Route("/nuevo", name="work_linked_training_agreement_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        MembershipRepository $membershipRepository,
        AgreementActivityRealizationRepository $agreementActivityRealizationRepository)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_WORK_LINKED_TRAINING, $organization);

        $agreement = new Agreement();
        $this->getDoctrine()->getManager()->persist($agreement);

        return $this->indexAction(
            $request,
            $userExtensionService,
            $translator,
            $membershipRepository,
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
        MembershipRepository $membershipRepository,
        AgreementActivityRealizationRepository $agreementActivityRealizationRepository,
        Agreement $agreement
    ) {
        if ($agreement->getId()) {
            $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);
        }

        $oldWorkTutor = $agreement->getWorkTutor();

        if (null === $agreement->getStudentEnrollment()) {
            $academicYear = $userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
        } else {
            $academicYear = $agreement->
                getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear();
        }

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(AgreementType::class, $agreement);

        $oldActivityRealizations = $agreement->getActivityRealizations();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // dar acceso al tutor laboral a la organización si ha cambiado
                if ($agreement->getWorkTutor() !== $oldWorkTutor && $agreement->getWorkTutor()->getUser()) {
                    $membershipRepository->addNewOrganizationMembership(
                        $academicYear->getOrganization(),
                        $agreement->getWorkTutor()->getUser(),
                        $academicYear->getStartDate(),
                        $academicYear->getEndDate()
                    );
                }
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

                if ($agreement->getId()) {
                    $toRemove = array_diff($oldActivityRealizations->toArray(), $currentActivityRealizations->toArray());
                    $agreementActivityRealizationRepository->deleteFromList($agreement, $toRemove);
                }

                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_agreement'));
                return $this->redirectToRoute('work_linked_training_agreement_list', [
                    'academicYear' => $academicYear
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_agreement'));
            }
        }

        $title = $translator->trans(
            $agreement->getId() ? 'title.edit' : 'title.new',
            [],
            'wlt_agreement'
        );

        $breadcrumb = [
            $agreement->getId() ?
                ['fixed' => (string) $agreement] :
                ['fixed' => $translator->trans('title.new', [], 'wlt_agreement')]
        ];

        return $this->render('wlt/agreement/form.html.twig', [
            'menu_path' => 'work_linked_training_agreement_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="work_linked_training_agreement_list",
     *     requirements={"page" = "\d+"}, defaults={"academicYear" = null, "page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Security $security,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_WORK_LINKED_TRAINING, $organization);

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
            ->from(Agreement::class, 'a')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->orderBy('g.name')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
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

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        if (false === $security->isGranted(OrganizationVoter::MANAGE, $organization) &&
            false === $security->isGranted(OrganizationVoter::WLT_MANAGER, $organization)
        ) {
            $queryBuilder
                ->join('t.department', 'd')
                ->join('d.head', 'ht')
                ->andWhere('ht.person = :person')
                ->setParameter('person', $this->getUser()->getPerson());
        }
        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $translator->trans('title.list', [], 'wlt_agreement');

        return $this->render('wlt/agreement/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_agreement',
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/operacion/{academicYear}", name="work_linked_training_agreement_operation", methods={"POST"})
     */
    public function operationAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(OrganizationVoter::WLT_MANAGER, $academicYear->getOrganization());

        if ($academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            return $this->createNotFoundException();
        }

        $items = $request->request->get('items', []);

        if (count($items) !== 0) {
            if ('' === $request->get('delete')) {
                return $this->deleteAction($items, $request, $translator, $agreementRepository, $academicYear);
            }
            if ('' === $request->get('copy')) {
                return $this->copyAction($items, $request, $translator, $agreementRepository, $academicYear);
            }
        }

        return $this->redirectToRoute(
            'work_linked_training_agreement_list',
            ['academicYear' => $academicYear->getId()]
        );
    }

    private function deleteAction(
        $items,
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        AcademicYear $academicYear
    ) {
        $em = $this->getDoctrine()->getManager();


        $agreements = $agreementRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

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
                ['academicYear' => $academicYear->getId()]
            );
        }

        return $this->render('wlt/agreement/delete.html.twig', [
            'menu_path' => 'work_linked_training_agreement_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'wlt_agreement')]],
            'title' => $translator->trans('title.delete', [], 'wlt_agreement'),
            'items' => $agreements
        ]);
    }

    private function copyAction(
        $items,
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        AcademicYear $academicYear
    ) {
        $em = $this->getDoctrine()->getManager();

        $selectedAgreements = $agreementRepository->findAllInListByIdAndAcademicYear($items, $academicYear);
        $agreementChoices = $agreementRepository->findAllInListByNotIdAndAcademicYear($items, $academicYear);

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
                    'academicYear' => $academicYear
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.copy_error', [], 'wlt_agreement'));
            }
        }
        $title = $translator->trans('title.copy', [], 'wlt_agreement');
        return $this->render('wlt/agreement/copy.html.twig', [
            'menu_path' => 'work_linked_training_agreement_list',
            'breadcrumb' => [['fixed' => $title]],
            'title' => $title,
            'form' => $form->createView(),
            'items' => $selectedAgreements
        ]);
    }
}
