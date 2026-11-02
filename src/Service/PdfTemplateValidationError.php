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

namespace App\Service;

/**
 * Motivos por los que un PDF no sirve como plantilla de fondo de los informes
 */
enum PdfTemplateValidationError
{
    /** No se puede leer como PDF (dañado, cifrado o con un formato que no se puede importar) */
    case InvalidPdf;

    /** Tiene más de una página: mPDF repite la primera en todas las hojas del informe */
    case MultiPage;
}
