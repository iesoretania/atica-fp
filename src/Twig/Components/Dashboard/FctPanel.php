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

use App\Controller\WptModule\TrackingController;
use App\Entity\Edu\AcademicYear;
use App\Entity\Person;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WptModule\GroupRepository;
use App\Security\WptModule\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Dashboard:FctPanel', template: 'components/Dashboard/TrackingPanel.html.twig')]
final class FctPanel extends AbstractTrackingPanel
{
    public function __construct(
        Security $security,
        UserExtensionService $userExtensionService,
        EntityManagerInterface $entityManager,
        private readonly GroupRepository $groupRepository,
        private readonly TeacherRepository $teacherRepository,
    ) {
        parent::__construct($security, $userExtensionService, $entityManager);
    }

    protected function accessAttribute(): string
    {
        return OrganizationVoter::WPT_ACCESS;
    }

    protected function visibleQueryBuilder(AcademicYear $academicYear, Person $person, bool $isManager): QueryBuilder
    {
        return TrackingController::generateAgreementQueryBuilder(
            $this->groupRepository,
            $this->teacherRepository,
            $academicYear,
            $this->entityManager->createQueryBuilder(),
            $person,
            $isManager,
            null
        );
    }

    protected function expressions(): array
    {
        return [
            'start' => 'a.startDate',
            'end' => 'a.endDate',
            'hours' => 'SUM(wd.hours)',
            'locked' => 'SUM(CASE WHEN twd.absence = 0 THEN twd.locked * wd.hours ELSE 0 END)',
            'absences' => 'SUM(CASE WHEN twd.absence != 0 THEN 1 ELSE 0 END)',
            'justified' => 'SUM(CASE WHEN twd.absence = 2 THEN 1 ELSE 0 END)',
            'sign' => 'a.signDate',
        ];
    }

    public function listRoute(): string
    {
        return 'workplace_training_tracking_list';
    }

    public function module(): string
    {
        return 'wpt';
    }
}
