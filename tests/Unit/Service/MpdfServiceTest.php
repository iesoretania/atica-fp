<?php

namespace App\Tests\Unit\Service;

use App\Service\MpdfService;
use Mpdf\Output\Destination;
use PHPUnit\Framework\TestCase;

class MpdfServiceTest extends TestCase
{
    public function testSourceSansProIsTheDefaultFont(): void
    {
        $mpdf = (new MpdfService())->getMpdf();

        self::assertSame(MpdfService::FONT_NAME, $mpdf->default_font);
    }

    public function testSourceSansProIsTheDefaultFontWithoutTheDefaultConstructorArguments(): void
    {
        $service = (new MpdfService())->setAddDefaultConstructorArgs(false);
        $mpdf = $service->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);

        self::assertSame(MpdfService::FONT_NAME, $mpdf->default_font);
    }

    public function testGeneratedPdfEmbedsSourceSansPro(): void
    {
        $pdf = (new MpdfService())->generatePdf(
            '<p>Informe de <b>seguimiento</b> de la formación en empresa: ñ, á, é, í, ó, ú</p>',
            ['outputDest' => Destination::STRING_RETURN]
        );

        self::assertStringStartsWith('%PDF', $pdf);
        self::assertStringContainsString('SourceSansPro', $pdf);
        self::assertStringNotContainsString('DejaVuSans', $pdf);
    }

    public function testTheCallerCanOverrideTheDefaultFont(): void
    {
        $service = (new MpdfService())->setAddDefaultConstructorArgs(false);
        $mpdf = $service->getMpdf([['mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans']]);

        self::assertSame('dejavusans', $mpdf->default_font);
    }
}
