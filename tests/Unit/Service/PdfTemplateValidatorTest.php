<?php

namespace App\Tests\Unit\Service;

use App\Service\PdfTemplateValidationError;
use App\Service\PdfTemplateValidator;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PHPUnit\Framework\TestCase;

class PdfTemplateValidatorTest extends TestCase
{
    private static function pdf(int $pages): string
    {
        $mpdf = new Mpdf(['tempDir' => sys_get_temp_dir()]);
        for ($page = 1; $page <= $pages; $page++) {
            if ($page > 1) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML('<p>Página ' . $page . '</p>');
        }

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    public function testSinglePagePdfIsValid(): void
    {
        self::assertNull((new PdfTemplateValidator())->validate(self::pdf(1)));
    }

    public function testPdfWithSeveralPagesIsRejected(): void
    {
        self::assertSame(PdfTemplateValidationError::MultiPage, (new PdfTemplateValidator())->validate(self::pdf(3)));
    }

    public function testBrokenContentIsRejected(): void
    {
        $validator = new PdfTemplateValidator();

        self::assertSame(PdfTemplateValidationError::InvalidPdf, $validator->validate(''));
        self::assertSame(PdfTemplateValidationError::InvalidPdf, $validator->validate('<?php echo 1;'));
        self::assertSame(PdfTemplateValidationError::InvalidPdf, $validator->validate("%PDF-1.4\nesto no es un pdf\n"));
    }
}
