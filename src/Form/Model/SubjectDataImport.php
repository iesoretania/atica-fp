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

use App\Entity\Edu\Grade;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class SubjectDataImport
{
    /**
     * @var Grade
     */
    private $grade;

    /**
     * @Assert\File
     * @var UploadedFile
     */
    private $file;

    public function __construct()
    {
    }

    /**
     * @return ?Grade
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param ?Grade $grade
     * @return self
     */
    public function setGrade(?Grade $grade)
    {
        $this->grade = $grade;
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
     * @return self
     */
    public function setFile(UploadedFile $file)
    {
        $this->file = $file;
        return $this;
    }
}
