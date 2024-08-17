<?php

/*
  ÁTICA - Aplicación web para la gestión documental de centros educativos

  Copyright (C) 2015-2017: Luis Ramón López López

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

  Adapted from: http://php.net/manual/es/function.fgetcsv.php#68213
*/

namespace App\Utils;

class CsvImporter
{
    private $fp;
    private array|bool|null $header = null;

    public function __construct($fileName, private $parse_header = true, private $delimiter = ",", private $length = 0)
    {
        $this->fp = fopen($fileName, 'rb');

        if ($this->parse_header) {
            $this->header = fgetcsv($this->fp, $this->length, $this->delimiter);
            foreach ($this->header as $key => $data) {
                $this->header[$key] = iconv('ISO-8859-1', 'UTF-8', (string) $data);
            }
        }
    }

    public function __destruct()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
    }

    /**
     * @return array[]
     */
    public function get($max_lines = 0): array
    {
        //if $max_lines is set to 0, then get all the data

        $data = [];

        if ($max_lines > 0) {
            $line_count = 0;
        } else {
            $line_count = -1; // so loop limit is ignored
        }

        while ($line_count < $max_lines && ($row = fgetcsv($this->fp, $this->length, $this->delimiter)) !== false) {
            foreach ($row as $key => $key_data) {
                $row[$key] = iconv('ISO-8859-1', 'UTF-8', (string) $key_data);
            }

            if ($this->parse_header) {
                $row_new = [];
                foreach ($this->header as $i => $heading_i) {
                    $row_new[$heading_i] = $row[$i];
                }

                $data[] = $row_new;
            } else {
                $data[] = $row;
            }

            if ($max_lines > 0) {
                $line_count++;
            }
        }
        return $data;
    }
}
