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
use App\Security\WltModule\OrganizationVoter;
use App\Service\UserExtensionService;
use App\Service\WltModule\TrackingQueryFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Dashboard:WltPanel', template: 'components/Dashboard/TrackingPanel.html.twig')]
final class WltPanel extends AbstractTrackingPanel
{
    public function __construct(
        Security $security,
        UserExtensionService $userExtensionService,
        EntityManagerInterface $entityManager,
        private readonly TrackingQueryFactory $trackingQueryFactory,
    ) {
        parent::__construct($security, $userExtensionService, $entityManager);
    }

    protected function accessAttribute(): string
    {
        return OrganizationVoter::WLT_ACCESS;
    }

    protected function visibleQueryBuilder(AcademicYear $academicYear, Person $person, bool $isManager): QueryBuilder
    {
        return $this->trackingQueryFactory->create(
            $academicYear,
            $person,
            $isManager,
            $this->security->isGranted(OrganizationVoter::WLT_MANAGER, $this->userExtensionService->getCurrentOrganization())
        );
    }

    protected function expressions(): array
    {
        return [
            'start' => 'a.startDate',
            'end' => 'a.endDate',
            'hours' => 'SUM(wd.hours)',
            'locked' => 'SUM(CASE WHEN wd.absence = 0 THEN wd.locked * wd.hours ELSE 0 END)',
            'absences' => 'SUM(CASE WHEN wd.absence != 0 THEN 1 ELSE 0 END)',
            'justified' => 'SUM(CASE WHEN wd.absence = 2 THEN 1 ELSE 0 END)',
            'sign' => null,
        ];
    }

    public function hoursDivisor(): int
    {
        return 1;
    }

    public function listRoute(): string
    {
        return 'work_linked_training_tracking_list';
    }

    public function module(): string
    {
        return 'wlt';
    }
}
