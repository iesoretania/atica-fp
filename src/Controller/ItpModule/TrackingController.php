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

namespace App\Controller\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Repository\WptModule\GroupRepository as WptGroupRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        AcademicYear                        $academicYear = null,
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

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $studentProgramWorkcenterRepository->createTrackingQueryBuilder(
            $academicYear,
            $person,
            $isManager,
            $q
        );

        $adapter = new QueryAdapter($queryBuilder);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'itp_tracking');

        return $this->render('itp/training_program/tracking/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_tracking',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/api/v2/acuerdo/listar', name: 'api_workplace_training_agreement_list', methods: ['GET'])]
    public function apiList(
        UserExtensionService $userExtensionService,
        WptGroupRepository   $groupRepository,
        TeacherRepository    $teacherRepository,
        ManagerRegistry      $managerRegistry
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $this->denyAccessUnlessGranted(WptOrganizationVoter::WPT_ACCESS, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();
        $person = $this->getUser();

        $queryBuilder = self::generateAgreementQueryBuilder(
            $groupRepository,
            $teacherRepository,
            $academicYear,
            $queryBuilder,
            $person,
            $isManager,
            ''
        );

        $agreements = $queryBuilder->getQuery()->getArrayResult();
        $agreements2 = [
            'agreements' => []
        ];
        foreach ($agreements as $agreement) {
            $agreements2['agreements'][] = [
                'agreement' => $agreement[0],
                'horas_totales' => $agreement[1],
                'horas_bloqueadas' => $agreement[2],
                'jornadas_sin_asistir' => $agreement[3],
                'faltas_justificadas' => $agreement[4]
            ];
        }
        return new JsonResponse($agreements2);
    }
}
