<?php
/*
  Copyright (C) 2026: Luis Ramón López López

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

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * El fichero subido es un PDF que se puede usar como plantilla de fondo de los informes
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
class ReportTemplatePdf extends Constraint
{
    public string $invalidMessage = 'error.invalid_pdf';
    public string $multiPageMessage = 'error.multi_page';
}
