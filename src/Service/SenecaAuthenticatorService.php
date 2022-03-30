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

namespace App\Service;

use phpseclib3\Math\BigInteger;

class SenecaAuthenticatorService
{
    /** @var string */
    private $url;

    /** @var boolean */
    private $forceSecurity;

    /** @var boolean */
    private $enabled;

    public function __construct($url, $forceSecurity, $enabled)
    {
        $this->url = $url;
        $this->forceSecurity = $forceSecurity;
        $this->enabled = $enabled;
    }

    /**
     * @param string $user
     * @param string $password
     * @return bool
     */
    public function checkUserCredentials($user, $password)
    {
        // devolver error si no está habilitado
        if (!$this->enabled) {
            return false;
        }

        $passwordCodificada = "";
        $password = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $password);
        for ($i = 0, $iMax = strlen($password); $i < $iMax; $i++) {
            $passwordCodificada .= ord($password[$i]);
        }

        $p = new BigInteger($passwordCodificada);

        $passwordCifrada = $p->powMod(
            new BigInteger('3584956249', 10),
            new BigInteger('356056806984207294102423357623243547284021', 10)
        )->toString();

        $fields = array(
            'USUARIO' => $user,
            'rndval' => random_int(10000000, 99999999),
            'CLAVECIFRADA' => $passwordCifrada,
            'CON_PRUEBA' => 'N',
            'N_V_' => 'NV_' . random_int(1, 9999),
            'NAV_WEB_NOMBRE' => 'Chrome',
            'NAV_WEB_VERSION' => '99',
            'C_INTERFAZ' => 'PASEN'

        );

        $str = $this->postToUrl($fields, $this->url, $this->url, $this->forceSecurity);

        if ($str === '') {
            return false;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($str);
        $xpath = new \DOMXPath($dom);
        $nav = $xpath->query('//correcto');

        return $nav->length === 1 && $nav->item(0)->textContent === "SI";
    }

    /**
     * Get URL contents
     *
     * @param string $url
     * @param boolean $forceSecurity
     * @return string
     */
    private function getUrl($url, $forceSecurity)
    {
        $curl = curl_init();
        $this->setCurlDefaultOptions($url, $forceSecurity, $curl);
        curl_setopt($curl, CURLOPT_REFERER, $url);
        $str = curl_exec($curl);
        curl_close($curl);
        return $str === false ? '' : (string) $str;
    }

    /**
     * Gets the content after POSTing into an URL
     *
     * @param array $fields
     * @param string $postUrl
     * @param string $refererUrl
     * @param boolean $forceSecurity
     * @return string
     */
    private function postToUrl($fields, $postUrl, $refererUrl, $forceSecurity)
    {
        $fieldsString = '';
        foreach ($fields as $key => $value) {
            $fieldsString .= $key.'='.rawurlencode($value).'&';
        }
        $fieldsString = rtrim($fieldsString, '&');

        $curl = curl_init();
        $this->setCurlDefaultOptions($postUrl, $forceSecurity, $curl);
        curl_setopt($curl, CURLOPT_REFERER, $refererUrl);
        curl_setopt($curl, CURLOPT_POST, count($fields));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fieldsString);
        $str = curl_exec($curl);
        curl_close($curl);
        return $str === false ? '' : (string) $str;
    }

    /**
     * @param $url
     * @param $forceSecurity
     * @param $curl
     */
    private function setCurlDefaultOptions($url, $forceSecurity, $curl)
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $forceSecurity);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 2);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curl,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) ' .
            'AppleWebKit/533.4 (KHTML, like Gecko) Chrome/5.0.375.125 Safari/533.4'
        );
    }
}
