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

namespace App\Entity\WLT;

use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Repository\WLT\MeetingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MeetingRepository::class)]
#[ORM\Table(name: 'wlt_meeting')]
class Meeting
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateTime = null;

    #[ORM\ManyToMany(targetEntity: StudentEnrollment::class, fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'wlt_meeting_student_enrollment')]
    private Collection $studentEnrollments;

    #[ORM\ManyToOne(targetEntity: Teacher::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Teacher $createdBy = null;

    #[ORM\ManyToMany(targetEntity: Teacher::class, fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'wlt_meeting_teacher')]
    private Collection $teachers;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detail = null;

    public function __construct()
    {
        $this->studentEnrollments = new ArrayCollection();
        $this->teachers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTimeInterface $dateTime): static
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    /**
     * @return Collection<int, StudentEnrollment>
     */
    public function getStudentEnrollments(): Collection
    {
        return $this->studentEnrollments;
    }

    public function setStudentEnrollments(Collection $studentEnrollments): static
    {
        $this->studentEnrollments = $studentEnrollments;
        return $this;
    }

    public function getCreatedBy(): ?Teacher
    {
        return $this->createdBy;
    }

    public function setCreatedBy(Teacher $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Collection<int, Teacher>
     */
    public function getTeachers(): Collection
    {
        return $this->teachers;
    }

    public function setTeachers(Collection $teachers): static
    {
        $this->teachers = $teachers;
        return $this;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail): static
    {
        $this->detail = $detail;
        return $this;
    }
}
