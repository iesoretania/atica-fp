<?php

namespace App\Entity\ItpModule;

use App\Entity\Edu\StudentEnrollment;
use App\Repository\ItpModule\StudentProgramRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StudentProgramRepository::class)]
#[ORM\Table(name: 'itp_student_program')]
class StudentProgram
{
    public const MODE_INHERITED = 0;
    public const MODE_GENERAL = 1;
    public const MODE_INTENSIVE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'studentPrograms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProgramGroup $programGroup = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentEnrollment $studentEnrollment = null;

    /**
     * @var Collection<int, StudentProgramWorkcenter>
     */
    #[ORM\OneToMany(targetEntity: StudentProgramWorkcenter::class, mappedBy: 'studentProgram', orphanRemoval: true)]
    private Collection $studentProgramWorkcenters;

    #[ORM\Column]
    private ?int $modality = self::MODE_INHERITED;

    #[ORM\Column]
    private ?bool $authorizationNeeded = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\When(
        expression: 'this.isAuthorizationNeeded() === true',
        constraints: [new Assert\NotBlank()]
    )]
    private ?string $authorizationDescription = null;

    #[ORM\Column]
    private ?bool $adaptationNeeded = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\When(
        expression: 'this.isAdaptationNeeded() === true',
        constraints: [new Assert\NotBlank()]
    )]
    private ?string $adaptationDescription = null;

    public function __construct()
    {
        $this->studentProgramWorkcenters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgramGroup(): ?ProgramGroup
    {
        return $this->programGroup;
    }

    public function setProgramGroup(ProgramGroup $programGroup): static
    {
        $this->programGroup = $programGroup;

        return $this;
    }

    public function getStudentEnrollment(): ?StudentEnrollment
    {
        return $this->studentEnrollment;
    }

    public function setStudentEnrollment(StudentEnrollment $studentEnrollment): static
    {
        $this->studentEnrollment = $studentEnrollment;

        return $this;
    }

    /**
     * @return Collection<int, StudentProgramWorkcenter>
     */
    public function getStudentProgramWorkcenters(): Collection
    {
        return $this->studentProgramWorkcenters;
    }

    public function getModality(): ?int
    {
        return $this->modality;
    }

    public function setModality(int $modality): static
    {
        $this->modality = $modality;

        return $this;
    }

    public function getActualModality(): ?int
    {
        return $this->modality === self::MODE_INHERITED ?
            $this->getProgramGroup()->getActualModality() :
            $this->modality;
    }

    public function isAuthorizationNeeded(): ?bool
    {
        return $this->authorizationNeeded;
    }

    public function setAuthorizationNeeded(bool $authorizationNeeded): static
    {
        $this->authorizationNeeded = $authorizationNeeded;

        return $this;
    }

    public function getAuthorizationDescription(): ?string
    {
        return $this->authorizationDescription;
    }

    public function setAuthorizationDescription(?string $authorizationDescription): static
    {
        $this->authorizationDescription = $authorizationDescription;

        return $this;
    }

    public function isAdaptationNeeded(): ?bool
    {
        return $this->adaptationNeeded;
    }

    public function setAdaptationNeeded(bool $adaptationNeeded): static
    {
        $this->adaptationNeeded = $adaptationNeeded;

        return $this;
    }

    public function getAdaptationDescription(): ?string
    {
        return $this->adaptationDescription;
    }

    public function setAdaptationDescription(?string $adaptationDescription): static
    {
        $this->adaptationDescription = $adaptationDescription;

        return $this;
    }
}
