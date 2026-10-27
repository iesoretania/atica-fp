<?php

namespace App\Tests\Unit\Utils;

use App\Utils\CsvImporter;
use PHPUnit\Framework\TestCase;

class CsvImporterTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    private function csv(string $content): string
    {
        $file = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($file, $content);
        $this->files[] = $file;

        return $file;
    }

    /**
     * Lee el fichero entero convirtiendo en excepción cualquier aviso de PHP, como hace Symfony en la aplicación
     *
     * @return array[]
     */
    private function read(string $content): array
    {
        set_error_handler(static function (int $severity, string $message): never {
            throw new \ErrorException($message, 0, $severity);
        });
        try {
            return (new CsvImporter($this->csv($content), true))->get();
        } finally {
            restore_error_handler();
        }
    }

    public function testReadsRowsIndexedByHeader(): void
    {
        $rows = $this->read("Nombre,Curso\nAna,1A\nLuis,2B\n");

        self::assertSame([['Nombre' => 'Ana', 'Curso' => '1A'], ['Nombre' => 'Luis', 'Curso' => '2B']], $rows);
    }

    public function testConvertsLatin1ToUtf8(): void
    {
        $rows = $this->read(mb_convert_encoding("Nombre\nMuñoz Peña\n", 'ISO-8859-1', 'UTF-8'));

        self::assertSame('Muñoz Peña', $rows[0]['Nombre']);
    }

    public function testHeaderOnlyFileHasNoRows(): void
    {
        self::assertSame([], $this->read("Nombre,Curso\n"));
    }

    public function testEmptyFileHasNoRows(): void
    {
        self::assertSame([], $this->read(''));
    }

    public function testBlankLinesAreSkipped(): void
    {
        $rows = $this->read("Nombre,Curso\nAna,1º A\n\n\nLuis,2º B\n\n");

        self::assertCount(2, $rows);
    }

    public function testShortRowsAreCompletedWithEmptyValues(): void
    {
        $rows = $this->read("Nombre,Curso,Tutor\nAna\nLuis,2º B\n");

        self::assertSame('', $rows[0]['Curso']);
        self::assertSame('', $rows[1]['Tutor']);
    }

    public function testExtraColumnsAreIgnored(): void
    {
        $rows = $this->read("Nombre\nAna,sobra,otra más\n");

        self::assertSame('Ana', $rows[0]['Nombre']);
    }

    public function testBinaryContentDoesNotRaiseErrors(): void
    {
        $rows = $this->read(random_bytes(2048));

        self::assertIsArray($rows);
    }

    public function testMaxLinesLimitsEachRead(): void
    {
        $importer = new CsvImporter($this->csv("N\n1\n2\n3\n"), true);

        self::assertCount(2, $importer->get(2));
        self::assertCount(1, $importer->get(2));
        self::assertSame([], $importer->get(2));
    }
}
