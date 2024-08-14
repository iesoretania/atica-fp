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

class TeacherImport
{
    private ?AcademicYear $academicYear = null;

    #[Assert\File]
    private ?UploadedFile $file = null;

    private bool $externalPassword = true;

    private bool $generatePassword = false;

    public function getAcademicYear(): ?AcademicYear
    {
        return $this->academicYear;
    }

    public function setAcademicYear(AcademicYear $academicYear): static
    {
        $this->academicYear = $academicYear;
        return $this;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }


    public function setFile(UploadedFile $file): static
    {
        $this->file = $file;
        return $this;
    }

    public function getGeneratePassword(): bool
    {
        return $this->generatePassword;
    }

    public function setGeneratePassword(bool $generatePassword): static
    {
        $this->generatePassword = $generatePassword;
        return $this;
    }

    public function isExternalPassword(): bool
    {
        return $this->externalPassword;
    }

    public function setExternalPassword(bool $externalPassword): static
    {
        $this->externalPassword = $externalPassword;
        return $this;
    }
}
