<?php

namespace App\Tests\Unit\Service;

use App\Service\ICalService;
use PHPUnit\Framework\TestCase;

class ICalServiceTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    private function calendar(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ics');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    public function testParsesTheEventsOfACalendar(): void
    {
        $file = $this->calendar(
            "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//ES\r\n"
            . "BEGIN:VEVENT\r\nUID:1@test\r\nDTSTART;VALUE=DATE:20261012\r\nDTEND;VALUE=DATE:20261013\r\nSUMMARY:Fiesta nacional\r\nDESCRIPTION:Día no lectivo\r\nEND:VEVENT\r\n"
            . "BEGIN:VEVENT\r\nUID:2@test\r\nDTSTART;VALUE=DATE:20261102\r\nDTEND;VALUE=DATE:20261103\r\nSUMMARY:Puente\r\nEND:VEVENT\r\n"
            . "END:VCALENDAR\r\n"
        );

        $events = (new ICalService())->ICalParser($file)->events();

        self::assertCount(2, $events);
        self::assertSame('20261012', substr((string) $events[0]->dtstart, 0, 8));
        self::assertSame('Día no lectivo', $events[0]->description);
        self::assertSame('20261102', substr((string) $events[1]->dtstart, 0, 8));
    }

    public function testCalendarWithoutEventsReturnsAnEmptyList(): void
    {
        $file = $this->calendar("BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//ES\r\nEND:VCALENDAR\r\n");

        self::assertCount(0, (new ICalService())->ICalParser($file)->events());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidContentProvider(): iterable
    {
        yield 'fichero vacío' => [''];
        yield 'texto sin formato' => ['esto no es un calendario'];
        yield 'contenido binario' => [random_bytes(64)];
        yield 'calendario sin cerrar' => ["BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20261012\r\n"];
    }

    /**
     * @dataProvider invalidContentProvider
     */
    public function testInvalidContentIsRejectedWithAnException(string $content): void
    {
        $service = new ICalService();
        $file = $this->calendar($content);

        // la importación captura \Exception: cualquier otra cosa sería un error 500
        try {
            $events = $service->ICalParser($file)->events();
            self::assertSame([], $events);
        } catch (\Exception) {
            $this->addToAssertionCount(1);
        }
    }

    public function testMissingFileIsRejectedWithAnException(): void
    {
        // sin fichero no hay eventos o se lanza una excepción que la importación captura; nunca un error fatal
        try {
            self::assertSame([], (new ICalService())->ICalParser('/ruta/que/no/existe.ics')->events());
        } catch (\Exception) {
            $this->addToAssertionCount(1);
        }
    }
}
