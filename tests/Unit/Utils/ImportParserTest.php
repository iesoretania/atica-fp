<?php

namespace App\Tests\Unit\Utils;

use App\Utils\ImportParser;
use PHPUnit\Framework\TestCase;

class ImportParserTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}> curso de Séneca, nivel y enseñanza esperados
     */
    public static function validGradeNameProvider(): iterable
    {
        yield 'ciclo de grado superior' => [
            '1º F.P.I.G.S. (Desarrollo de Aplicaciones Web)',
            '1º Desarrollo de Aplicaciones Web',
            'F.P.I.G.S. Desarrollo de Aplicaciones Web',
        ];
        yield 'sin ordinal' => [
            'O.C.C.E.G.S. (Inteligencia Artificial y Big Data)',
            'Inteligencia Artificial y Big Data',
            'O.C.C.E.G.S. Inteligencia Artificial y Big Data',
        ];
        yield 'bachillerato' => ['2º de Bachillerato (Ciencias)', '2º Bachillerato', 'Bachillerato'];
        yield 'sin parentesis ni ordinal' => ['Educación Infantil', 'Educación Infantil', 'Educación Infantil'];
    }

    /**
     * @dataProvider validGradeNameProvider
     */
    public function testParsesSenecaGradeNames(string $gradeName, string $grade, string $training): void
    {
        [$parsedGrade, $parsedTraining] = ImportParser::parseGradeName($gradeName);

        self::assertSame($grade, $parsedGrade);
        self::assertSame($training, $parsedTraining);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedGradeNameProvider(): iterable
    {
        yield 'vacío' => [''];
        yield 'ciclo sin paréntesis' => ['1º F.P.I.G.S. Desarrollo de Aplicaciones Web'];
        yield 'ciclo sin ordinal ni paréntesis' => ['F.P.B. Informática'];
        yield 'paréntesis sin cerrar' => ['1º F.P.I.G.S. (Desarrollo'];
        yield 'ordinal sin texto' => ['º '];
    }

    /**
     * Un curso mal formado en el fichero no debe provocar un error del servidor
     *
     * @dataProvider malformedGradeNameProvider
     */
    public function testMalformedGradeNamesDoNotRaiseErrors(string $gradeName): void
    {
        set_error_handler(static function (int $severity, string $message): never {
            throw new \ErrorException($message, 0, $severity);
        });
        try {
            $result = ImportParser::parseGradeName($gradeName);
        } finally {
            restore_error_handler();
        }

        self::assertCount(2, $result);
        self::assertIsString($result[0]);
        self::assertIsString($result[1]);
    }
}
