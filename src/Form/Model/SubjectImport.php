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

namespace App\Form\Model;

use App\Entity\Edu\AcademicYear;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class SubjectImport
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
    private $extractTeachers;

    /**
     * @var bool
     */
    private $keepOneSubjectPerTraining;

    public function __construct()
    {
        $this->extractTeachers = true;
        $this->keepOneSubjectPerTraining = true;
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
     * @return SubjectImport
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
     * @return SubjectImport
     */
    public function setFile(UploadedFile $file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExtractTeachers()
    {
        return $this->extractTeachers;
    }

    /**
     * @param bool $extractTeachers
     * @return SubjectImport
     */
    public function setExtractTeachers($extractTeachers)
    {
        $this->extractTeachers = $extractTeachers;
        return $this;
    }

    /**
     * @return bool
     */
    public function isKeepOneSubjectPerTraining()
    {
        return $this->keepOneSubjectPerTraining;
    }

    /**
     * @param bool $keepOneSubjectPerTraining
     * @return SubjectImport
     */
    public function setKeepOneSubjectPerTraining($keepOneSubjectPerTraining)
    {
        $this->keepOneSubjectPerTraining = $keepOneSubjectPerTraining;
        return $this;
    }
}
