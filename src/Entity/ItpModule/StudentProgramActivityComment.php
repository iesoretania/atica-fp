<?php

namespace App\Entity\ItpModule;

use App\Repository\ItpModule\StudentProgramActivityCommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentProgramActivityCommentRepository::class)]
#[ORM\Table(name: 'itp_student_program_activity_comment')]
class StudentProgramActivityComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentProgramActivity $studentProgramActivity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudentProgramActivity(): ?StudentProgramActivity
    {
        return $this->studentProgramActivity;
    }

    public function setStudentProgramActivity(?StudentProgramActivity $studentProgramActivity): static
    {
        $this->studentProgramActivity = $studentProgramActivity;

        return $this;
    }
}
