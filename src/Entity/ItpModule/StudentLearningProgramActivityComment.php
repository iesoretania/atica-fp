<?php

namespace App\Entity\ItpModule;

use App\Repository\ItpModule\StudentLearningProgramActivityCommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentLearningProgramActivityCommentRepository::class)]
class StudentLearningProgramActivityComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentLearningProgramActivity $studentLearningProgramActivity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudentLearningProgramActivity(): ?StudentLearningProgramActivity
    {
        return $this->studentLearningProgramActivity;
    }

    public function setStudentLearningProgramActivity(?StudentLearningProgramActivity $studentLearningProgramActivity): static
    {
        $this->studentLearningProgramActivity = $studentLearningProgramActivity;

        return $this;
    }
}
