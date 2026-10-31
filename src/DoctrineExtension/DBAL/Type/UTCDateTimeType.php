<?php
/**
 * From: http://atlantic18.github.io/DoctrineExtensions/doc/timestampable.html
 */

namespace App\DoctrineExtension\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\Exception\InvalidFormat;

class UTCDateTimeType extends DateTimeType
{
    private static ?\DateTimeZone $utc = null;

    final public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
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

    final public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateTime
    {
        if ($value === null) {
            return null;
        }

        if (!self::$utc instanceof \DateTimeZone) {
            self::$utc = new \DateTimeZone('UTC');
        }

        $val = \DateTime::createFromFormat($platform->getDateTimeFormatString(), $value, self::$utc);

        if (!$val) {
            throw InvalidFormat::new($value, self::class, $platform->getDateTimeFormatString());
        }

        return $val;
    }
}
