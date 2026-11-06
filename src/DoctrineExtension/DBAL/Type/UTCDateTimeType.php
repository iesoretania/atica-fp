<?php
/**
 * From: http://atlantic18.github.io/DoctrineExtensions/doc/timestampable.html
 */

namespace App\DoctrineExtension\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

/**
 * Guarda las fechas con hora en UTC y las devuelve en la zona horaria por defecto de PHP (APP_TIMEZONE), de
 * forma que tanto Twig como cualquier ->format() en el código muestren la hora local.
 */
class UTCDateTimeType extends DateTimeType
{
    private static ?\DateTimeZone $utc = null;

    final public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        // Se trabaja sobre una copia: no se debe alterar la zona horaria de la entidad al guardarla
        $utcValue = (clone $value)->setTimezone(self::utc());

        return $utcValue->format($platform->getDateTimeFormatString());
    }

    final public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        $val = \DateTime::createFromFormat($platform->getDateTimeFormatString(), $value, self::utc());

        if (!$val) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }

    private static function utc(): \DateTimeZone
    {
        return self::$utc ??= new \DateTimeZone('UTC');
    }
}
