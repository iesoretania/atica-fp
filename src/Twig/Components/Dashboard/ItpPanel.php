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

namespace App\Twig\Components\Dashboard;

use App\Entity\Edu\AcademicYear;
use App\Entity\Person;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\ItpModule\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Dashboard:ItpPanel', template: 'components/Dashboard/TrackingPanel.html.twig')]
final class ItpPanel extends AbstractTrackingPanel
{
    public function __construct(
        Security $security,
        UserExtensionService $userExtensionService,
        EntityManagerInterface $entityManager,
        private readonly StudentProgramWorkcenterRepository $repository,
    ) {
        parent::__construct($security, $userExtensionService, $entityManager);
    }

    protected function accessAttribute(): string
    {
        return OrganizationVoter::ITP_ACCESS_TRACKING;
    }

    protected function visibleQueryBuilder(AcademicYear $academicYear, Person $person, bool $isManager): QueryBuilder
    {
        return $this->repository->createTrackingQueryBuilder($academicYear, $person, $isManager, null);
    }

    protected function expressions(): array
    {
        return [
            'start' => 'spw.startDate',
            'end' => 'spw.endDate',
            'hours' => 'SUM(wd.hours)',
            'locked' => 'SUM(CASE WHEN wd.absence = 0 THEN wd.locked * wd.hours ELSE 0 END)',
            'absences' => 'SUM(CASE WHEN wd.absence != 0 THEN 1 ELSE 0 END)',
            'justified' => 'SUM(CASE WHEN wd.absence = 2 THEN 1 ELSE 0 END)',
            'sign' => null,
        ];
    }

    public function listRoute(): string
    {
        return 'in_company_training_phase_tracking_list';
    }

    public function module(): string
    {
        return 'itp';
    }
}
