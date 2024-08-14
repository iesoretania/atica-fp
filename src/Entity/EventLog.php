<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_log')]
class EventLog
{
    public const ACCESS = 'access';
    public const LOGIN_SUCCESS = 'login';
    public const LOGIN_ERROR = 'login_error';
    public const SWITCH_USER = 'switch_user';
    public const LOGOUT = 'logout';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateTime = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $event = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $data = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $dataAttachment = null;

    #[ORM\ManyToOne(targetEntity: Person::class)]
    private ?Person $user = null;

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

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;
        return $this;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(string $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData($data): static
    {
        $this->data = substr((string) $data, 0, 255);
        return $this;
    }

    public function getDataAttachment(): ?string
    {
        return $this->dataAttachment;
    }

    public function setDataAttachment(?string $dataAttachment): static
    {
        $this->dataAttachment = $dataAttachment;
        return $this;
    }

    public function getUser(): ?Person
    {
        return $this->user;
    }

    public function setUser(?Person $user): static
    {
        $this->user = $user;
        return $this;
    }
}
