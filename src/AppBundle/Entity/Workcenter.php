<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="workcenter")
 */
class Workcenter
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="workcenters")
     * @ORM\JoinColumn(nullable=false)
     * @var Company
     */
    private $company;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $address;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $zipCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $faxNumber;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Email
     * @var string
     */
    private $emailAddress;

    /**
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(nullable=true)
     * @var Person
     */
    private $manager;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param Company $company
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return string
     */
    public function getFaxNumber()
    {
        return $this->faxNumber;
    }

    /**
     * @param string $faxNumber
     */
    public function setFaxNumber($faxNumber)
    {
        $this->faxNumber = $faxNumber;
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param string $emailAddress
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return Person|null
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param Person|null $manager
     */
    public function setManager($manager = null)
    {
        $this->manager = $manager;
    }
}