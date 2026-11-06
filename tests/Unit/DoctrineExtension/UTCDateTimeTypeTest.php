<?php

namespace App\Tests\Unit\DoctrineExtension;

use App\DoctrineExtension\DBAL\Type\UTCDateTimeType;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

class UTCDateTimeTypeTest extends TestCase
{
    private string $previousTimezone;

    protected function setUp(): void
    {
        $this->previousTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Madrid');
        if (!Type::hasType('utc_test')) {
            Type::addType('utc_test', UTCDateTimeType::class);
        }
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->previousTimezone);
    }

    public function testReadsUtcAndReturnsLocalTime(): void
    {
        $value = Type::getType('utc_test')->convertToPHPValue('2026-07-01 10:00:00', new MySQLPlatform());

        $this->assertSame('2026-07-01 12:00:00', $value->format('Y-m-d H:i:s'));
        $this->assertSame('Europe/Madrid', $value->getTimezone()->getName());
    }

    public function testStoresUtcWithoutAlteringTheEntityValue(): void
    {
        $value = new \DateTime('2026-01-15 09:30:00', new \DateTimeZone('Europe/Madrid'));

        $stored = Type::getType('utc_test')->convertToDatabaseValue($value, new MySQLPlatform());

        $this->assertSame('2026-01-15 08:30:00', $stored);
        $this->assertSame('09:30', $value->format('H:i'));
    }

    public function testNullValues(): void
    {
        $type = Type::getType('utc_test');

        $this->assertNull($type->convertToPHPValue(null, new MySQLPlatform()));
        $this->assertNull($type->convertToDatabaseValue(null, new MySQLPlatform()));
    }
}
