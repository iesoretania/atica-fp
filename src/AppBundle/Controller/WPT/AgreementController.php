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

namespace AppBundle\Controller\WPT;

use AppBundle\Entity\WPT\Agreement;
use AppBundle\Entity\WPT\Shift;
use AppBundle\Form\Model\WPT\CalendarCopy;
use AppBundle\Form\Type\WPT\AgreementType;
use AppBundle\Form\Type\WPT\CalendarCopyType;
use AppBundle\Repository\MembershipRepository;
use AppBundle\Repository\WPT\ActivityRepository;
use AppBundle\Repository\WPT\AgreementRepository;
use AppBundle\Security\WPT\AgreementVoter;
use AppBundle\Security\WPT\ShiftVoter;
use AppBundle\Security\WPT\WPTOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

/**
 * @Route("/fct/acuerdo")
 */
class AgreementController extends Controller
{
    /**
     * @Route("/nuevo/{shift}", name="workplace_training_agreement_new",
     *     requirements={"shift": "\d+"}, methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        MembershipRepository $membershipRepository,
        Shift $shift
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);

        $agreement = new Agreement();
        $agreement
            ->setShift($shift);

        $this->getDoctrine()->getManager()->persist($agreement);

        return $this->indexAction(
            $request,
            $userExtensionService,
            $translator,
            $membershipRepository,
            $agreement
        );
    }

    /**
     * @Route("/{id}", name="workplace_training_agreement_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        MembershipRepository $membershipRepository,
        Agreement $agreement
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);
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
            'disabled' => $readOnly,
            'academic_year' => $academicYear,
            'new' => !$agreement->getId()
        ]);

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

                if (!$agreement->getId()) {
                    $enrollments = $form->get('studentEnrollments')->getData();
                    foreach ($enrollments as $enrollment) {
                        $newAgreement = clone $agreement;
                        $newAgreement->setStudentEnrollment($enrollment);
                        $em->persist($newAgreement);
                    }
                    $em->remove($agreement);
                }

                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_agreement'));
                return $this->redirectToRoute('workplace_training_agreement_list', [
                    'id' => $agreement->getShift()->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_agreement'));
            }
        }

        $title = $translator->trans(
            $agreement->getId() ? 'title.edit' : 'title.new',
            [],
            'wpt_agreement'
        );

        $breadcrumb = [
            [
                'fixed' => $agreement->getShift()->getName(),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.agreements', [], 'wpt_shift'),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            $agreement->getId() ?
                ['fixed' => (string) $agreement] :
                ['fixed' => $translator->trans('title.new', [], 'wpt_agreement')]
        ];

        return $this->render('wpt/agreement/form.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'agreement' => $agreement,
            'read_only' => $readOnly
        ]);
    }

    /**
     * @Route("/{id}/listar/{page}", name="workplace_training_agreement_list",
     *     requirements={"page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Shift $shift,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        if ($shift) {
            $this->denyAccessUnlessGranted(ShiftVoter::MANAGE, $shift);
        } else {
            $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);
        }

        if ($shift && $shift->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $organization) {
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
            ->join('a.shift', 'shi')
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
                ->orWhere('shi.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'wpt_agreement');

        $breadcrumb = [
            ['fixed' => $shift->getName()],
            ['fixed' => $translator->trans('title.agreements', [], 'wpt_shift')]
        ];

        return $this->render('wpt/agreement/list.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_agreement',
            'shift' => $shift
        ]);
    }

    /**
     * @Route("/operacion/{shift}", name="workplace_training_agreement_operation",
     *     requirements={"shift": "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        Shift $shift
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);

        $items = $request->request->get('items', []);

        if (count($items) !== 0) {
            if ('' === $request->get('delete')) {
                return $this->deleteAction($items, $request, $translator, $agreementRepository, $shift);
            }
            if ('' === $request->get('copy')) {
                return $this->copyAction($items, $request, $translator, $agreementRepository, $shift);
            }
        }

        return $this->redirectToRoute(
            'workplace_training_agreement_list',
            ['id' => $shift->getId()]
        );
    }

    private function deleteAction(
        $items,
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        Shift $shift
    ) {
        $em = $this->getDoctrine()->getManager();

        $agreements = $agreementRepository->findAllInListByIdAndShift($items, $shift);

        // comprobar individualmente que tenemos acceso
        foreach ($agreements as $agreement) {
            $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                $agreementRepository->deleteFromList($agreements);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wpt_agreement'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wpt_agreement'));
            }
            return $this->redirectToRoute(
                'workplace_training_agreement_list',
                ['id' => $shift->getId()]
            );
        }

        $breadcrumb = [
            [
                'fixed' => $shift->getName(),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            ['fixed' => $translator->trans('title.delete', [], 'wpt_agreement')]
        ];

        return $this->render('wpt/agreement/delete.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'wpt_agreement'),
            'items' => $agreements
        ]);
    }

    private function copyAction(
        $items,
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        Shift $shift
    ) {
        $em = $this->getDoctrine()->getManager();

        $selectedAgreements = $agreementRepository->findAllInListByIdAndShift($items, $shift);
        // comprobar individualmente que tenemos acceso
        foreach ($selectedAgreements as $agreement) {
            $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);
        }
        $agreementChoices = $agreementRepository->findAllInListByNotIdAndShift($items, $shift);
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
                $this->addFlash('success', $translator->trans('message.copied', [], 'wpt_agreement'));
                return $this->redirectToRoute('workplace_training_agreement_list', [
                    'id' => $shift->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.copy_error', [], 'wpt_agreement'));
            }
        }

        $title = $translator->trans('title.copy', [], 'wpt_agreement');
        $breadcrumb = [
            [
                'fixed' => $shift->getName(),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $shift->getId()]
            ],
            ['fixed' => $title]
        ];
        return $this->render('wpt/agreement/copy.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'items' => $selectedAgreements
        ]);
    }

    /**
     * @Route("/programa/descargar/{id}", name="workplace_training_agreement_program_report", methods={"GET"})
     */
    public function downloadTeachingProgramReportAction(
        TranslatorInterface $translator,
        ActivityRepository $activityRepository,
        Environment $twig,
        Agreement $agreement
    ) {
        $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);

        $title = $translator->trans('form.training_program', [], 'wpt_program_report')
            . ' - ' . $agreement->getStudentEnrollment() . ' - ' . $agreement->getWorkcenter();

        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            $template = $agreement
                ->getShift()->getGrade()->getTraining()->getAcademicYear()->getDefaultLandscapeTemplate();

            if ($template) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetImportUse();
                $mpdf->SetDocTemplate($tmp);
            }

            $mpdf->SetFont('DejaVuSansCondensed');
            $mpdf->SetFontSize(9);

            $mpdf->WriteHTML($twig->render('wpt/agreement/training_program_report.html.twig', [
                'agreement' => $agreement,
                'title' => $title,
                'learning_program' => $activityRepository->getProgramActivitiesFromAgreement($agreement)
            ]));

            $fileName = $title . '.pdf';

            $mpdf->SetTitle($title);

            $response = new Response();
            $response->headers->set('Content-Type', 'application/pdf');
            $response->setContent($mpdf->Output($fileName, Destination::STRING_RETURN));

            $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

            return $response;
        } finally {
            if ($tmp) {
                unlink($tmp);
            }
        }
    }
}
