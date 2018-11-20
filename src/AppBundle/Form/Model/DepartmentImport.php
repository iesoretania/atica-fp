<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

class DepartmentImport
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
     * @var bool
     */
    private $extractHeads;

    public function __construct()
    {
        $this->restricted = true;
        $this->extractHeads = true;
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
     * @return DepartmentImport
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
     * @return DepartmentImport
     */
    public function setFile(UploadedFile $file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRestricted()
    {
        return $this->restricted;
    }

    /**
     * @param bool $restricted
     * @return DepartmentImport
     */
    public function setRestricted($restricted)
    {
        $this->restricted = $restricted;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExtractHeads()
    {
        return $this->extractHeads;
    }

    /**
     * @param bool $extractHeads
     * @return DepartmentImport
     */
    public function setExtractHeads($extractHeads)
    {
        $this->extractHeads = $extractHeads;
        return $this;
    }
}
