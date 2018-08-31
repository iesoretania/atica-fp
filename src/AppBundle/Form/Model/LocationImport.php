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

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class LocationImport
{
    /**
     * @Assert\File
     * @var UploadedFile
     */
    private $file;

    /**
     * @var boolean
     */
    private $onlyKeepNew;

    /**
     * TeacherImport constructor.
     */
    public function __construct()
    {
        $this->onlyKeepNew = false;
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
     * @return LocationImport
     */
    public function setFile(UploadedFile $file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return bool
     */
    public function getOnlyKeepNew()
    {
        return $this->onlyKeepNew;
    }

    /**
     * @param bool $onlyKeepNew
     * @return LocationImport
     */
    public function setOnlyKeepNew($onlyKeepNew)
    {
        $this->onlyKeepNew = $onlyKeepNew;
        return $this;
    }
}
