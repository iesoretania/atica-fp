<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        if (!$this->booted) {
            self::applyTimezone();
        }

        parent::boot();
    }

    /**
     * Fija la zona horaria de PHP con la variable de entorno APP_TIMEZONE, para que la aplicación muestre la misma
     * hora local en cualquier despliegue (Docker, Apache, PHP-FPM, consola...) sin depender del php.ini.
     * Si la variable no está definida o está vacía se respeta la zona horaria configurada en PHP.
     */
    private static function applyTimezone(): void
    {
        $timezone = $_SERVER['APP_TIMEZONE'] ?? $_ENV['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE');

        if (!is_string($timezone) || trim($timezone) === '') {
            return;
        }

        try {
            new \DateTimeZone(trim($timezone));
        } catch (\Exception) {
            throw new \InvalidArgumentException(sprintf('El valor de APP_TIMEZONE ("%s") no es una zona horaria válida (por ejemplo, "Europe/Madrid").', $timezone));
        }

        date_default_timezone_set(trim($timezone));
    }
}
