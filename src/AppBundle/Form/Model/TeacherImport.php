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

namespace AppBundle\Form\Model;

use AppBundle\Entity\Edu\AcademicYear;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class TeacherImport
{
    /**
     * @var AcademicYear
     */
    private $academicYear;

    /**
     * @Assert\File
     * @var UploadedFile
     */
    private $file;

    /**
     * @var boolean
     */
    private $externalPassword;

    /**
     * @var boolean
     */
    private $generatePassword;

    /**
     * TeacherImport constructor.
     */
    public function __construct()
    {
        $this->generatePassword = false;
        $this->externalPassword = true;
    }

    /**
     * @return AcademicYear
     */
    public function getAcademicYear()
    {
        return $this->academicYear;
    }

    /**
     * @param AcademicYear $academicYear
     * @return TeacherImport
     */
    public function setAcademicYear($academicYear)
    {
        $this->academicYear = $academicYear;
        return $this;
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     *
     * @return TeacherImport
     */
    public function setFile(UploadedFile $file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getGeneratePassword()
    {
        return $this->generatePassword;
    }

    /**
     * @param boolean $generatePassword
     * @return TeacherImport
     */
    public function setGeneratePassword($generatePassword)
    {
        $this->generatePassword = $generatePassword;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExternalPassword()
    {
        return $this->externalPassword;
    }

    /**
     * @param bool $externalPassword
     * @return TeacherImport
     */
    public function setExternalPassword($externalPassword)
    {
        $this->externalPassword = $externalPassword;
        return $this;
    }
}
