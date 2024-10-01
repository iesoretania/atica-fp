<?php

namespace App\Entity\ItpModule;

use App\Entity\Edu\Criterion;
use App\Repository\ItpModule\ActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Table(name: 'itp_activity')]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'activities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProgramGrade $programGrade = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Criterion>
     */
    #[ORM\ManyToMany(targetEntity: Criterion::class)]
    #[ORM\JoinTable(name: 'itp_activity_criterion')]
    private Collection $criteria;

    public function __construct()
    {
        $this->criteria = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgramGrade(): ?ProgramGrade
    {
        return $this->programGrade;
    }

    public function setProgramGrade(ProgramGrade $programGrade): static
    {
        $this->programGrade = $programGrade;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Criterion>
     */
    public function getCriteria(): Collection
    {
        return $this->criteria;
    }

    public function addCriterion(Criterion $criterion): static
    {
        if (!$this->criteria->contains($criterion)) {
            $this->criteria->add($criterion);
        }

        return $this;
    }

    public function removeCriterion(Criterion $criterion): static
    {
        $this->criteria->removeElement($criterion);

        return $this;
    }
}
