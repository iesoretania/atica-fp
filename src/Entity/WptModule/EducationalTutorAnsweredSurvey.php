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

namespace App\Entity\WptModule;

use App\Entity\AnsweredSurvey;
use App\Entity\Edu\Teacher;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'wpt_educational_tutor_answered_survey')]
class EducationalTutorAnsweredSurvey
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Shift::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Shift $shift = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Teacher $teacher = null;

    #[ORM\ManyToOne(targetEntity: AnsweredSurvey::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?AnsweredSurvey $answeredSurvey = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShift(): ?Shift
    {
        return $this->shift;
    }

    public function setShift(Shift $shift): static
    {
        $this->shift = $shift;
        return $this;
    }

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(Teacher $teacher): static
    {
        $this->teacher = $teacher;
        return $this;
    }

    public function getAnsweredSurvey(): ?AnsweredSurvey
    {
        return $this->answeredSurvey;
    }

    public function setAnsweredSurvey(AnsweredSurvey $answeredSurvey): static
    {
        $this->answeredSurvey = $answeredSurvey;
        return $this;
    }
}
