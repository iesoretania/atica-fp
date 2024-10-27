<?php

namespace App\Entity\ItpModule;

use App\Repository\ItpModule\StudentProgramWorkcenterActivityCommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentProgramWorkcenterActivityCommentRepository::class)]
#[ORM\Table(name: 'itp_student_program_workcenter_activity_comment')]
class StudentProgramWorkcenterActivityComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentProgramWorkcenterActivity $studentProgramActivity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudentProgramActivity(): ?StudentProgramWorkcenterActivity
    {
        return $this->studentProgramActivity;
    }

    public function setStudentProgramActivity(?StudentProgramWorkcenterActivity $studentProgramActivity): static
    {
        $this->studentProgramActivity = $studentProgramActivity;

        return $this;
    }
}
