<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Controller\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\Person;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/seguimiento')]
class TrackingController extends AbstractController
{
    #[Route(path: '/listar/{academicYear}/{page}', name: 'in_company_training_phase_tracking_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                             $request,
        UserExtensionService                $userExtensionService,
        TranslatorInterface                 $translator,
        AcademicYearRepository              $academicYearRepository,
        StudentProgramWorkcenterRepository  $studentProgramWorkcenterRepository,
        ?AcademicYear                       $academicYear = null,
        int                                 $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_SECTION, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $q = $request->get('q');
        $person = $this->getUser();
        assert($person instanceof Person);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $studentProgramWorkcenterRepository->createTrackingQueryBuilder(
            $academicYear,
            $person,
            $isManager,
            $q
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

        $title = $translator->trans('title.list', [], 'itp_tracking');

        return $this->render('itp/tracking/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_tracking',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/exportar/{academicYear}', name: 'in_company_training_phase_tracking_export', requirements: ['academicYear' => '\\d+'], methods: ['GET'])]
    public function export(
        Request                             $request,
        UserExtensionService                $userExtensionService,
        StudentProgramWorkcenterRepository  $studentProgramWorkcenterRepository,
        ?AcademicYear                       $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_SECTION, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);
        $q = $request->get('q');
        $person = $this->getUser();
        assert($person instanceof Person);

        $items = $studentProgramWorkcenterRepository->createTrackingQueryBuilder(
            $academicYear,
            $person,
            $isManager,
            $q
        )
            ->getQuery()
            ->getResult();

        $filename = sprintf('estancias_ffeoe_%s.csv', (new \DateTimeImmutable())->format('Y-m-d'));

        return new StreamedResponse(function () use ($items): void {
            $output = fopen('php://output', 'wb');
            if (!is_resource($output)) {
                return;
            }

            // BOM UTF-8 para mejorar compatibilidad con Excel.
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, [
                'Estudiante',
                'Grupo',
                'Nivel',
                'Enseñanza',
                'Centro de trabajo',
                'Tutor/a docente de seguimiento',
                'Tutor/a dual de empresa',
                'Fecha de inicio',
                'Fecha de fin',
                'Horas totales',
                'Horas de asistencia',
            ], ';');

            foreach ($items as $item) {
                $spw = $item[0] ?? null;
                if (!$spw instanceof StudentProgramWorkcenter) {
                    continue;
                }

                $enrollment = $spw->getStudentProgram()?->getStudentEnrollment();
                $group = $enrollment?->getGroup();
                $grade = $group?->getGrade();
                $hours = isset($item['hours']) ? (float) $item['hours'] / 100.0 : 0.0;
                $attendanceHours = isset($item['locked_hours']) ? (float) $item['locked_hours'] / 100.0 : 0.0;

                fputcsv($output, [
                    (string) ($enrollment?->getPerson() ?? ''),
                    (string) ($group?->getName() ?? ''),
                    (string) ($grade?->getName() ?? ''),
                    (string) ($grade?->getTraining()?->getName() ?? ''),
                    (string) ($spw->getWorkcenter() ?? ''),
                    (string) ($spw->getEducationalTutor() ?? ''),
                    (string) ($spw->getWorkTutor() ?? ''),
                    $spw->getStartDate()?->format('d/m/Y') ?? '',
                    $spw->getEndDate()?->format('d/m/Y') ?? '',
                    number_format($hours, 2, ',', ''),
                    number_format($attendanceHours, 2, ',', ''),
                ], ';');
            }

            fclose($output);
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

}
