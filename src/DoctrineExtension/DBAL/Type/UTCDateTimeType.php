<?php
/**
 * From: http://atlantic18.github.io/DoctrineExtensions/doc/timestampable.html
 */

namespace App\DoctrineExtension\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

class UTCDateTimeType extends DateTimeType
{
    private static ?\DateTimeZone $utc = null;

    final public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!self::$utc instanceof \DateTimeZone) {
            self::$utc = new \DateTimeZone('UTC');
        }

        $value->setTimeZone(self::$utc);

        return $value->format($platform->getDateTimeFormatString());
    }

    final public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!self::$utc instanceof \DateTimeZone) {
            self::$utc = new \DateTimeZone('UTC');
        }

        $val = \DateTime::createFromFormat($platform->getDateTimeFormatString(), $value, self::$utc);

        if (!$val) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }
}
