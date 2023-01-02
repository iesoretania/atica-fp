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

namespace App\Entity\WLT;

use App\Entity\Person;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WLT\AgreementActivityRealizationCommentRepository")
 * @ORM\Table(name="wlt_agreement_activity_realization_comment")
 */
class AgreementActivityRealizationComment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AgreementActivityRealization", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     * @var AgreementActivityRealization
     */
    private $agreementActivityRealization;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Person")
     * @ORM\JoinColumn(nullable=true)
     * @var Person
     */
    private $person;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @var string
     */
    private $comment;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AgreementActivityRealization
     */
    public function getAgreementActivityRealization()
    {
        return $this->agreementActivityRealization;
    }

    /**
     * @param AgreementActivityRealization $agreementActivityRealization
     * @return AgreementActivityRealizationComment
     */
    public function setAgreementActivityRealization(AgreementActivityRealization $agreementActivityRealization)
    {
        $this->agreementActivityRealization = $agreementActivityRealization;
        return $this;
    }

    /**
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param Person $person
     * @return AgreementActivityRealizationComment
     */
    public function setPerson(Person $person)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return AgreementActivityRealizationComment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    /**
     * @param \DateTime $timestamp
     * @return AgreementActivityRealizationComment
     */
    public function setTimestamp(\DateTime $timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }
}
