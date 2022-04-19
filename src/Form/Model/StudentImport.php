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

namespace App\Form\Model;

use App\Entity\Edu\AcademicYear;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class StudentImport
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
    private $overwriteUserNames;

    public function __construct()
    {
        $this->overwriteUserNames = false;
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
     * @return StudentImport
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
     * @param UploadedFile $file
     * @return StudentImport
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return bool
     */
    public function getOverwriteUserNames(): bool
    {
        return $this->overwriteUserNames;
    }

    /**
     * @param bool $overwriteUserNames
     * @return StudentImport
     */
    public function setOverwriteUserNames(bool $overwriteUserNames)
    {
        $this->overwriteUserNames = $overwriteUserNames;
        return $this;
    }
}
