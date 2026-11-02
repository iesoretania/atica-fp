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

use Mpdf\Mpdf;
use setasign\Fpdi\FpdiException;

/**
 * Comprueba que un PDF subido sirve como plantilla de fondo de los informes: debe poder leerse y
 * tener una sola página, porque SetDocTemplate repite esa página en todas las hojas que se generan
 * y varias páginas darían un resultado confuso.
 */
final class PdfTemplateValidator
{
    public function validate(string $content): ?PdfTemplateValidationError
    {
        $path = tempnam(sys_get_temp_dir(), 'pdfval_');
        if ($path === false) {
            return PdfTemplateValidationError::InvalidPdf;
        }

        try {
            file_put_contents($path, $content);

            $mpdf = new Mpdf(['tempDir' => sys_get_temp_dir()]);
            $pageCount = $mpdf->setSourceFile($path);

            if ($pageCount !== 1) {
                return PdfTemplateValidationError::MultiPage;
            }

            // Se importa la página para detectar contenido que no se pueda usar como plantilla
            $mpdf->importPage(1);

            return null;
        } catch (FpdiException) {
            return PdfTemplateValidationError::InvalidPdf;
        } finally {
            @unlink($path);
        }
    }
}
