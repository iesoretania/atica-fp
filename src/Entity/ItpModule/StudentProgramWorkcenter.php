<?php

namespace App\Entity\ItpModule;

use App\Entity\Workcenter;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentProgramWorkcenterRepository::class)]
#[ORM\Table(name: 'itp_student_program_workcenter')]
class StudentProgramWorkcenter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'studentProgramWorkcenters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentProgram $studentProgram = null;

    /**
     * @var Collection<int, WorkDay>
     */
    #[ORM\OneToMany(targetEntity: WorkDay::class, mappedBy: 'studentProgramWorkcenter', orphanRemoval: true)]
    private Collection $workDays;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workcenter $workcenter = null;

    public function __construct()
    {
        $this->workDays = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudentProgram(): ?StudentProgram
    {
        return $this->studentProgram;
    }

    public function setStudentProgram(?StudentProgram $studentProgram): static
    {
        $this->studentProgram = $studentProgram;

        return $this;
    }

    /**
     * @return Collection<int, WorkDay>
     */
    public function getWorkDays(): Collection
    {
        return $this->workDays;
    }

    public function getWorkcenter(): ?Workcenter
    {
        return $this->workcenter;
    }

    public function setWorkcenter(?Workcenter $workcenter): static
    {
        $this->workcenter = $workcenter;

        return $this;
    }
}
