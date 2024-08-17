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

namespace App\Entity\WptModule;

use App\Entity\Workcenter;
use App\Repository\WptModule\AgreementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AgreementRepository::class)]
#[ORM\Table(name: 'wpt_agreement')]
class Agreement implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Shift::class, inversedBy: 'agreements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Shift $shift = null;

    #[ORM\ManyToOne(targetEntity: Workcenter::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workcenter $workcenter = null;

    /**
     * @var Collection<int, AgreementEnrollment>
     */
    #[ORM\OneToMany(targetEntity: AgreementEnrollment::class, mappedBy: 'agreement')]
    private Collection $agreementEnrollments;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $signDate = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $defaultStartTime1 = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $defaultEndTime1 = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $defaultStartTime2 = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $defaultEndTime2 = null;

    /**
     * @var Collection<int, WorkDay>
     */
    #[ORM\OneToMany(targetEntity: WorkDay::class, mappedBy: 'agreement')]
    private Collection $workDays;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $locked = null;

    public function __construct()
    {
        $this->agreementEnrollments = new ArrayCollection();
        $this->workDays = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (!$this->getShift() instanceof Shift || !$this->getWorkcenter() instanceof Workcenter) ? ''
            : $this->getShift()->__toString()
            . ' - '
            . $this->getWorkcenter()->__toString()
            . ($this->getName() !== '' && $this->getName() !== null ? ' - ' . $this->getName() : '');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getShift(): ?Shift
    {
        return $this->shift;
    }

    public function setShift(Shift $shift): static
    {
        $this->shift = $shift;
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getSignDate(): ?\DateTimeInterface
    {
        return $this->signDate;
    }

    public function setSignDate(?\DateTimeInterface $signDate): static
    {
        $this->signDate = $signDate;
        return $this;
    }

    public function getDefaultStartTime1(): ?string
    {
        return $this->defaultStartTime1;
    }

    public function setDefaultStartTime1(?string $defaultStartTime1): static
    {
        $this->defaultStartTime1 = $defaultStartTime1;
        return $this;
    }

    public function getDefaultEndTime1(): ?string
    {
        return $this->defaultEndTime1;
    }

    public function setDefaultEndTime1(?string $defaultEndTime1): static
    {
        $this->defaultEndTime1 = $defaultEndTime1;
        return $this;
    }

    public function getDefaultStartTime2(): ?string
    {
        return $this->defaultStartTime2;
    }

    public function setDefaultStartTime2(?string $defaultStartTime2): static
    {
        $this->defaultStartTime2 = $defaultStartTime2;
        return $this;
    }

    public function getDefaultEndTime2(): ?string
    {
        return $this->defaultEndTime2;
    }

    public function setDefaultEndTime2(?string $defaultEndTime2): static
    {
        $this->defaultEndTime2 = $defaultEndTime2;
        return $this;
    }

    /**
     * @return Collection<int, WorkDay>
     */
    public function getWorkDays(): Collection
    {
        return $this->workDays;
    }

    /**
     * @return Collection<int, AgreementEnrollment>
     */
    public function getAgreementEnrollments(): Collection
    {
        return $this->agreementEnrollments;
    }

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;
        return $this;
    }
}
