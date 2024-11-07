<?php

namespace App\Entity\ItpModule;

use App\Entity\Person;
use App\Repository\ItpModule\StudentProgramWorkcenterActivityCommentRepository;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(type: Types::TEXT)]
    private ?string $comment = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $timestamp = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Person $person = null;

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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeImmutable $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): static
    {
        $this->person = $person;

        return $this;
    }
}
