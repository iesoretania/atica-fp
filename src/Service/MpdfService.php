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

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pequeño envoltorio sobre mPDF. Sustituye al bundle abandonado tfox/mpdf-port-bundle
 * (incompatible con Symfony 7 y 8), conservando el mismo comportamiento y API.
 *
 * Todos los documentos usan por defecto el tipo de letra Source Sans Pro (config/pdf/fonts/).
 */
class MpdfService
{
    /** Clave de la fuente en mPDF: minúsculas y sin espacios, como las que trae mPDF (dejavusans...) */
    public const FONT_NAME = 'sourcesanspro';

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

        // Si quien llama indica alguna opción de fuente, prevalece sobre la de por defecto
        if (is_array($allConstructorArgs[0] ?? null)) {
            $allConstructorArgs[0] += self::fontConfig();
        }

        return new Mpdf(...$allConstructorArgs);
    }

    /**
     * Registra Source Sans Pro como fuente de mPDF y la establece como fuente por defecto del documento, de modo
     * que los informes la usen sin pedirla cada uno.
     *
     * @return array<string, mixed>
     */
    public static function fontConfig(): array
    {
        $configDefaults = (new ConfigVariables())->getDefaults();
        $fontDefaults = (new FontVariables())->getDefaults();

        $fontDirs = is_array($configDefaults['fontDir'] ?? null) ? $configDefaults['fontDir'] : [];
        $fontData = is_array($fontDefaults['fontdata'] ?? null) ? $fontDefaults['fontdata'] : [];

        return [
            'fontDir' => array_merge($fontDirs, [dirname(__DIR__, 2) . '/config/pdf/fonts']),
            'fontdata' => $fontData + [
                self::FONT_NAME => [
                    'R' => 'SourceSansPro-Regular.ttf',
                    'B' => 'SourceSansPro-Bold.ttf',
                    'I' => 'SourceSansPro-It.ttf',
                    'BI' => 'SourceSansPro-BoldIt.ttf',
                ],
            ],
            'default_font' => self::FONT_NAME,
        ];
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
