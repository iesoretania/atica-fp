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

namespace App\Repository\WltModule;

use App\Entity\Edu\Competency;
use App\Entity\Edu\Group;
use App\Entity\WltModule\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CompetencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Competency::class);
    }

    /**
     * @return Competency[]
     */
    public function findByProject(Project $training): array
    {
        $result = [];

        /** @var Group $group */
        foreach ($training->getGroups() as $group) {
            $competencies = $group->getGrade()->getTraining()->getCompetencies();
            foreach ($competencies as $competency) {
                $result[$competency->getCode()] = $competency;
            }
        }

        return $result;
    }
}
