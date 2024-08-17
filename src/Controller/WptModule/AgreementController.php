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

namespace App\Controller\WptModule;

use App\Entity\Edu\ReportTemplate;
use App\Entity\WptModule\Agreement;
use App\Entity\WptModule\AgreementEnrollment;
use App\Entity\WptModule\Shift;
use App\Form\Model\WptModule\CalendarCopy;
use App\Form\Type\WptModule\AgreementEnrollmentType;
use App\Form\Type\WptModule\AgreementType;
use App\Form\Type\WptModule\CalendarCopyType;
use App\Repository\WptModule\ActivityRepository;
use App\Repository\WptModule\AgreementEnrollmentRepository;
use App\Repository\WptModule\AgreementRepository;
use App\Security\WptModule\AgreementVoter;
use App\Security\WptModule\ShiftVoter;
use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

#[Route(path: '/fct/acuerdo')]
class AgreementController extends AbstractController
{
    #[Route(path: '/nuevo/{shift}', name: 'workplace_training_agreement_new', requirements: ['shift' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Shift $shift
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WptOrganizationVoter::WPT_MANAGER, $organization);

        $agreement = new Agreement();
        $agreement
            ->setShift($shift)
            ->setLocked(false);

        $managerRegistry->getManager()->persist($agreement);

        return $this->index(
            $request,
            $userExtensionService,
            $translator,
            $managerRegistry,
            $agreement
        );
    }

    #[Route(path: '/{id}', name: 'workplace_training_agreement_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Agreement $agreement
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WptOrganizationVoter::WPT_MANAGER, $organization);
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);
        $readOnly = !$this->isGranted(AgreementVoter::MANAGE, $agreement);

        $academicYear = $organization->getCurrentAcademicYear();

        $em = $managerRegistry->getManager();

        $currentStudentEnrollments = new ArrayCollection();
        foreach ($agreement->getAgreementEnrollments() as $agreementEnrollment) {
            $currentStudentEnrollments->add($agreementEnrollment->getStudentEnrollment());
        }
        $form = $this->createForm(AgreementType::class, $agreement, [
            'disabled' => $readOnly,
            'new' => null === $agreement->getId(),
            'academic_year' => $academicYear
        ]);

        $form->get('studentEnrollments')->setData($currentStudentEnrollments);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $enrollments = $form->get('studentEnrollments')->getData();
                foreach ($enrollments as $studentEnrollment) {
                    if (!$currentStudentEnrollments->contains($studentEnrollment)) {
                        $agreementEnrollment = new AgreementEnrollment();
                        $agreementEnrollment
                            ->setAgreement($agreement)
                            ->setStudentEnrollment($studentEnrollment)
                            ->setEducationalTutor($form->get('educationalTutor')->getData())
                            ->setWorkTutor($form->get('workTutor')->getData())
                            ->setActivities($form->get('activities')->getData());
                        $em->persist($agreementEnrollment);
                    }
                }
                foreach ($agreement->getAgreementEnrollments() as $agreementEnrollment) {
                    if (!$enrollments->contains($agreementEnrollment->getStudentEnrollment())) {
                        $agreement->getAgreementEnrollments()->removeElement($agreementEnrollment);
                        $em->remove($agreementEnrollment);
                    }
                }

                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_agreement'));
                return $this->redirectToRoute('workplace_training_agreement_list', [
                    'id' => $agreement->getShift()->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_agreement'));
            }
        }

        $title = $translator->trans(
            $agreement->getId() !== null ? 'title.edit' : 'title.new',
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
            $agreement->getId() !== null ?
                ['fixed' => $agreement->__toString()] :
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

    #[Route(path: '/estudiante/{id}', name: 'workplace_training_agreement_enrollment_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function enrollmentEdit(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AgreementEnrollment $agreementEnrollment
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WptOrganizationVoter::WPT_MANAGER, $organization);
        $agreement = $agreementEnrollment->getAgreement();
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);
        $readOnly = !$this->isGranted(AgreementVoter::MANAGE, $agreement);

        $academicYear = $organization->getCurrentAcademicYear();

        $em = $managerRegistry->getManager();

        $form = $this->createForm(AgreementEnrollmentType::class, $agreementEnrollment, [
            'disabled' => $readOnly,
            'academic_year' => $academicYear
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_agreement'));
                return $this->redirectToRoute('workplace_training_agreement_list', [
                    'id' => $agreement->getShift()->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_agreement'));
            }
        }

        $title = $translator->trans(
            $agreement->getId() !== null ? 'title.edit' : 'title.new',
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
            $agreement->getId() !== null ?
                ['fixed' => $agreement->getWorkcenter()] :
                ['fixed' => $translator->trans('title.new', [], 'wpt_agreement')]
        ];

        return $this->render('wpt/agreement/enrollment_form.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'agreement' => $agreement,
            'read_only' => $readOnly
        ]);
    }

    #[Route(path: '/{id}/listar/{page}', name: 'workplace_training_agreement_list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementEnrollmentRepository $agreementEnrollmentRepository,
        Shift $shift,
        int $page = 1
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(ShiftVoter::MANAGE, $shift);

        if ($shift->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $organization) {
            throw $this->createAccessDeniedException();
        }

        $q = $request->get('q');
        $queryBuilder = $agreementEnrollmentRepository->findByShiftAndFilter($shift, $q);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
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

    #[Route(path: '/operacion/{shift}', name: 'workplace_training_agreement_operation', requirements: ['shift' => '\d+'], methods: ['POST'])]
    public function operation(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        ManagerRegistry $managerRegistry,
        Shift $shift
    ): Response|RedirectResponse {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WptOrganizationVoter::WPT_MANAGER, $organization);

        $items = $request->request->all('items');

        if ((is_countable($items) ? count($items) : 0) !== 0) {
            if ('' === $request->get('delete')) {
                return $this->delete($items, $request, $translator, $agreementRepository, $managerRegistry, $shift);
            }
            if ('' === $request->get('copy')) {
                return $this->copy($items, $request, $translator, $agreementRepository, $managerRegistry, $shift);
            }
        }

        return $this->redirectToRoute(
            'workplace_training_agreement_list',
            ['id' => $shift->getId()]
        );
    }

    private function delete(
        $items,
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        ManagerRegistry $managerRegistry,
        Shift $shift
    ): Response
    {
        $agreement = null;
        $em = $managerRegistry->getManager();

        $agreements = $agreementRepository->findAllInListByIdAndShift($items, $shift);

        // comprobar individualmente que tenemos acceso
        foreach ($agreements as $agreement) {
            $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);
        }

        if ($request->get('confirm', '') === 'ok') {
            //try {
                $agreementRepository->deleteFromList($agreements);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wpt_agreement'));
            //} catch (\Exception $e) {
            //    $this->addFlash('error', $translator->trans('message.delete_error', [], 'wpt_agreement'));
            //}
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

    private function copy(
        $items,
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        ManagerRegistry $managerRegistry,
        Shift $shift
    ): Response
    {
        $em = $managerRegistry->getManager();

        $academicYear = $shift->getGrade()->getTraining()->getAcademicYear();
        $selectedAgreements = $agreementRepository->findAllInListByIdAndAcademicYear($items, $academicYear);
        // comprobar individualmente que tenemos acceso
        foreach ($selectedAgreements as $agreement) {
            $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);
        }
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
                $this->addFlash('success', $translator->trans('message.copied', [], 'wpt_agreement'));
                return $this->redirectToRoute('workplace_training_agreement_list', [
                    'id' => $shift->getId()
                ]);
            } catch (\Exception) {
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

    #[Route(path: '/programa/descargar/{id}', name: 'workplace_training_agreement_program_report', methods: ['GET'])]
    public function downloadTeachingProgramReport(
        TranslatorInterface $translator,
        ActivityRepository $activityRepository,
        Environment $twig,
        AgreementEnrollment $agreementEnrollment
    ): Response {
        $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreementEnrollment->getAgreement());

        $title = $translator->trans('form.training_program', [], 'wpt_program_report')
            . ' - ' . $agreementEnrollment->getStudentEnrollment()->__toString() . ' - '
            . $agreementEnrollment->getAgreement()->getWorkcenter()->__toString();

        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);
        ini_set("pcre.backtrack_limit", "5000000");

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            $template = $agreementEnrollment
                ->getAgreement()->getShift()->getGrade()
                ->getTraining()->getAcademicYear()->getDefaultLandscapeTemplate();

            if ($template instanceof ReportTemplate) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetDocTemplate($tmp, true);
            }

            $mpdf->SetFont('DejaVuSansCondensed');
            $mpdf->SetFontSize(9);

            $mpdf->WriteHTML($twig->render('wpt/agreement/training_program_report.html.twig', [
                'agreement' => $agreementEnrollment->getAgreement(),
                'agreement_enrollment' => $agreementEnrollment,
                'title' => $title,
                'learning_program' => $activityRepository
                    ->getProgramActivitiesFromAgreementEnrollment($agreementEnrollment)
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