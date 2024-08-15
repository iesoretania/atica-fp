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

namespace App\Controller\Organization\Import;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\NonWorkingDay;
use App\Form\Model\NonWorkingDayImport;
use App\Form\Type\Import\NonWorkingDayImportType;
use App\Repository\Edu\NonWorkingDayRepository;
use App\Security\OrganizationVoter;
use App\Service\ICalService;
use App\Service\UserExtensionService;
use Doctrine\ORM\EntityManagerInterface;
use ICal\Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class NonWorkingDayImportController extends AbstractController
{
    #[Route(path: '/centro/importar/dia_no_lectivo', name: 'organization_import_non_working_day_form', methods: ['GET', 'POST'])]
    public function index(
        UserExtensionService $userExtensionService,
        EntityManagerInterface $entityManager,
        NonWorkingDayRepository $nonWorkingDayRepository,
        TranslatorInterface $translator,
        ICalService $iCalService,
        Request $request
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new NonWorkingDayImport();
        $formData->setAcademicYear($organization->getCurrentAcademicYear());

        $form = $this->createForm(NonWorkingDayImportType::class, $formData);
        $form->handleRequest($request);

        $stats = null;
        $breadcrumb = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importFromCsv(
                $formData->getFile()->getPathname(),
                $formData->getAcademicYear(),
                $nonWorkingDayRepository,
                $entityManager,
                $iCalService
            );

            if (null !== $stats) {
                $this->addFlash('success', $translator->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $translator->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $translator->trans('message.import_error', [], 'import'));
            }
        }
        $title = $translator->trans('title.non_working_day.import', [], 'import');

        return $this->render('admin/organization/import/non_working_day_import_form.html.twig', [
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'stats' => $stats
        ]);
    }

    /**
     * @param string $file
     *
     *
     * @throws \Exception
     */
    private function importFromCsv(
        $file,
        AcademicYear $academicYear,
        NonWorkingDayRepository $nonWorkingDayRepository,
        EntityManagerInterface $entityManager,
        ICalService $iCalService
    ): array {
        $newCount = 0;
        $oldCount = 0;

        $events = $iCalService->ICalParser($file)->events();

        $current = $nonWorkingDayRepository->findByAcademicYear($academicYear);

        $currentData = [];
        foreach ($current as $nonWorkingDay) {
            $currentData[$nonWorkingDay->getDate()->format('Ymd')] = $nonWorkingDay;
        }

        /** @var Event $event */
        foreach ($events as $event) {
            $index = substr($event->dtstart, 0, 8);
            if (!isset($currentData[$index])) {
                $newCount++;
                $nonWorkingDay = new NonWorkingDay();
                $nonWorkingDay
                    ->setAcademicYear($academicYear)
                    ->setDescription($event->description)
                    ->setDate(new \DateTime($event->dtstart));

                $entityManager->persist($nonWorkingDay);
            } else {
                $oldCount++;
            }
        }

        $entityManager->flush();

        return [
            'new_items' => $newCount,
            'old_items' => $oldCount
        ];
    }
}
