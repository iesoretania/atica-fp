<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="event_log")
 */
class EventLog
{
    public const ACCESS = 'access';
    public const LOGIN_SUCCESS = 'login';
    public const LOGIN_ERROR = 'login_error';
    public const SWITCH_USER = 'switch_user';
    public const LOGOUT = 'logout';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $dateTime;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $ip;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $event;

    /**
     * @ORM\Column(type="string", nullable=true, length=255)
     * @var string
     */
    private $data;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $dataAttachment;

    /**
     * @ORM\ManyToOne(targetEntity="Person")
     * @var Person
     */
    private $user;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param \DateTime $dateTime
     * @return EventLog
     */
    public function setDateTime(\DateTimeInterface $dateTime)
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return EventLog
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param string $event
     * @return EventLog
     */
    public function setEvent($event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return EventLog
     */
    public function setData($data)
    {
        $this->data = substr($data, 0, 255);
        return $this;
    }

    /**
     * @return string
     */
    public function getDataAttachment()
    {
        return $this->dataAttachment;
    }

    /**
     * @param string $dataAttachment
     * @return EventLog
     */
    public function setDataAttachment($dataAttachment)
    {
        $this->dataAttachment = $dataAttachment;
        return $this;
    }

    /**
     * @return Person
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Person|null $user
     * @return EventLog
     */
    public function setUser(Person $user = null)
    {
        $this->user = $user;
        return $this;
    }
}
