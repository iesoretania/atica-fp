<?php

namespace App\Entity\ItpModule;

use App\Entity\Company;
use App\Repository\ItpModule\CompanyProgramRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyProgramRepository::class)]
#[ORM\Table(name: 'itp_company_program')]
class CompanyProgram
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'companyPrograms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TrainingProgram $trainingProgram = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $agreementNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $monitoringInstruments = null;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\ManyToMany(targetEntity: Activity::class)]
    #[ORM\JoinTable(name: 'itp_company_program_activity')]
    private Collection $programActivities;

    public function __construct()
    {
        $this->programActivities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrainingProgram(): ?TrainingProgram
    {
        return $this->trainingProgram;
    }

    public function setTrainingProgram(TrainingProgram $trainingProgram): static
    {
        $this->trainingProgram = $trainingProgram;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getAgreementNumber(): ?string
    {
        return $this->agreementNumber;
    }

    public function setAgreementNumber(?string $agreementNumber): static
    {
        $this->agreementNumber = $agreementNumber;

        return $this;
    }

    public function getMonitoringInstruments(): ?string
    {
        return $this->monitoringInstruments;
    }

    public function setMonitoringInstruments(string $monitoringInstruments): static
    {
        $this->monitoringInstruments = $monitoringInstruments;

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getProgramActivities(): Collection
    {
        return $this->programActivities;
    }

    public function addProgramActivity(Activity $programActivity): static
    {
        if (!$this->programActivities->contains($programActivity)) {
            $this->programActivities->add($programActivity);
        }

        return $this;
    }

    public function removeProgramActivity(Activity $programActivity): static
    {
        $this->programActivities->removeElement($programActivity);

        return $this;
    }
}
