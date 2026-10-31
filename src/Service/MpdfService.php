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
use Symfony\Component\HttpFoundation\Response;

/**
 * Pequeño envoltorio sobre mPDF. Sustituye al bundle abandonado tfox/mpdf-port-bundle
 * (incompatible con Symfony 7 y 8), conservando el mismo comportamiento y API.
 */
class MpdfService
{
    private bool $addDefaultConstructorArgs = true;

    /**
     * Devuelve una instancia de mPDF.
     *
     * @param array $constructorArgs Argumentos para el constructor de mPDF
     */
    public function getMpdf(array $constructorArgs = []): Mpdf
    {
        $allConstructorArgs = $constructorArgs;
        if ($this->getAddDefaultConstructorArgs()) {
            $allConstructorArgs = [array_merge(['utf-8', 'A4'], $allConstructorArgs)];
        }

        return new Mpdf(...$allConstructorArgs);
    }

    /**
     * Devuelve una cadena con el contenido de un documento PDF.
     *
     * @param string $html Contenido HTML
     * @param array $argOptions Opciones: constructorArgs, writeHtmlMode, writeHtmlInitialise,
     *                          writeHtmlClose, outputFilename, outputDest y mpdf
     */
    public function generatePdf(string $html, array $argOptions = []): string
    {
        $options = array_merge([
            'constructorArgs' => [],
            'writeHtmlMode' => null,
            'writeHtmlInitialise' => null,
            'writeHtmlClose' => null,
            'outputFilename' => '',
            'outputDest' => 'S',
            'mpdf' => null
        ], $argOptions);

        $mpdf = $options['mpdf'] ?? $this->getMpdf($options['constructorArgs']);

        $writeHtmlArgs = array_filter(
            [$options['writeHtmlMode'], $options['writeHtmlInitialise'], $options['writeHtmlClose']],
            static fn($x) => $x !== null
        );
        $writeHtmlArgs['html'] = $html;

        @call_user_func_array([$mpdf, 'WriteHTML'], $writeHtmlArgs);

        return $mpdf->Output($options['outputFilename'], $options['outputDest']);
    }

    /**
     * Genera una respuesta HTTP con el documento PDF.
     */
    public function generatePdfResponse(string $html, array $argOptions = []): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContent($this->generatePdf($html, $argOptions));

        return $response;
    }

    public function setAddDefaultConstructorArgs(bool $val): static
    {
        $this->addDefaultConstructorArgs = $val;

        return $this;
    }

    public function getAddDefaultConstructorArgs(): bool
    {
        return $this->addDefaultConstructorArgs;
    }
}
