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

namespace App\Utils;

class ImportParser
{

    /**
     * @param string $gradeName
     * @return array
     */
    public static function parseGradeName($gradeName)
    {
        if (false === strpos($gradeName, 'F.P.')) {
            // Si no lleva la cadena F.P., eliminar el texto entre paréntesis y quitar 'de '
            // '2º de Bachillerato (Ciencias)' -> '2º de Bachillerato'
            $calculatedGradeName = trim(preg_replace('/(\(.*\))/', '', $gradeName));
            $calculatedGradeName = trim(preg_replace('/de /', '', $calculatedGradeName));

            $matches = [];
            // Enseñanza: Si el texto lleva 'º ' quedarse con el texto que le sigue
            // '2º de Bachillerato (Ciencias)' -> 'Bachillerato'
            //
            // Si no, dejarlo tal cual
            if (false !== strpos($gradeName, 'º ')) {
                preg_match('/º (.*)/u', $calculatedGradeName, $matches);
                $trainingName = $matches[1];
            } else {
                $trainingName = $calculatedGradeName;
            }
        } else {
            // Si lleva la cadena F.P.
            //
            // Nivel: coger los dos primeros caracteres + texto entre paréntesis
            // '1º F.P.I.G.S. (Desarrollo de Aplicaciones Web)' ->
            // '1º Desarrollo de Aplicaciones Web'
            preg_match('/º.*(F\.P.*)\(([^\)\(]*)\)/u', $gradeName, $matches);
            $calculatedGradeName = mb_substr($gradeName, 0, 2) . ' ' . $matches[2];

            // Enseñanza: Coger el texto que empieza por F.P. + texto entre paréntesis
            // 'F.P.I.G.S. Desarrollo de Aplicaciones Web'
            $trainingName = $matches[1] . $matches[2];
        }
        return [$calculatedGradeName, $trainingName];
    }
}
