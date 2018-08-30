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

namespace AppBundle\Utils;

class CsvImporter
{
    private $fp;
    private $parse_header;
    private $header;
    private $delimiter;
    private $length;

    public function __construct($fileName, $parseHeader = true, $delimiter = ",", $length = 0)
    {
        $this->fp = fopen($fileName, 'rb');
        $this->parse_header = $parseHeader;
        $this->delimiter = $delimiter;
        $this->length = $length;

        if ($this->parse_header) {
            $this->header = fgetcsv($this->fp, $this->length, $this->delimiter);
            foreach ($this->header as $key => $data) {
                $this->header[$key] = iconv('ISO-8859-1', 'UTF-8', $data);
            }
        }

    }

    public function __destruct()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
    }

    public function get($max_lines = 0)
    {
        //if $max_lines is set to 0, then get all the data

        $data = array();

        if ($max_lines > 0) {
            $line_count = 0;
        } else {
            $line_count = -1; // so loop limit is ignored
        }

        while ($line_count < $max_lines && ($row = fgetcsv($this->fp, $this->length, $this->delimiter)) !== FALSE)
        {
            foreach ($row as $key => $key_data) {
                $row[$key] = iconv('ISO-8859-1', 'UTF-8', $key_data);
            }

            if ($this->parse_header) {
                $row_new = array();
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
