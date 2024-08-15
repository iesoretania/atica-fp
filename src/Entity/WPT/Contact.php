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

namespace App\Entity\WPT;

use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Workcenter;
use App\Repository\WPT\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\Table(name: 'wpt_contact')]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateTime = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Teacher $teacher = null;

    #[ORM\ManyToOne(targetEntity: Workcenter::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workcenter $workcenter = null;

    /**
     * @var Collection<int, Agreement>
     */
    #[ORM\ManyToMany(targetEntity: Agreement::class)]
    #[ORM\JoinTable(name: 'wpt_contact_agreement')]
    private Collection $agreements;

    /**
     * @var Collection<int, StudentEnrollment>
     */
    #[ORM\ManyToMany(targetEntity: StudentEnrollment::class, fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'wpt_contact_student_enrollment')]
    private Collection $studentEnrollments;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detail = null;

    #[ORM\ManyToOne(targetEntity: ContactMethod::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ContactMethod $method = null;

    public function __construct()
    {
        $this->agreements = new ArrayCollection();
        $this->studentEnrollments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(Teacher $teacher): static
    {
        $this->teacher = $teacher;
        return $this;
    }

    public function getWorkcenter(): ?Workcenter
    {
        return $this->workcenter;
    }

    public function setWorkcenter(Workcenter $workcenter): static
    {
        $this->workcenter = $workcenter;
        return $this;
    }

    /**
     * @return Collection<int, Agreement>
     */
    public function getAgreements(): Collection
    {
        return $this->agreements;
    }

    public function setAgreements(Collection $agreements): static
    {
        $this->agreements = $agreements;
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

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail): static
    {
        $this->detail = $detail;
        return $this;
    }

    public function getMethod(): ?ContactMethod
    {
        return $this->method;
    }

    public function setMethod(?ContactMethod $method): Contact
    {
        $this->method = $method;
        return $this;
    }
}
