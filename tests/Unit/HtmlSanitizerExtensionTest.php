<?php

namespace App\Tests\Unit;

use App\Twig\HtmlSanitizerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class HtmlSanitizerExtensionTest extends TestCase
{
    private function extension(): HtmlSanitizerExtension
    {
        // misma configuración que framework.html_sanitizer.sanitizers.app.rich_text
        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowMediaSchemes(['http', 'https']);

        return new HtmlSanitizerExtension(new HtmlSanitizer($config));
    }

    public function testRemovesScriptsAndEventHandlers(): void
    {
        $result = $this->extension()->sanitize(
            '<p onclick="x()">Hola<script>alert(1)</script><img src="x" onerror="alert(2)"></p>'
        );

        self::assertStringNotContainsString('<script', $result);
        self::assertStringNotContainsString('alert', $result);
        self::assertStringNotContainsString('onclick', $result);
        self::assertStringNotContainsString('onerror', $result);
        self::assertStringContainsString('Hola', $result);
    }

    public function testRemovesJavascriptLinks(): void
    {
        $result = $this->extension()->sanitize('<a href="javascript:alert(1)">x</a><a href="https://ok.es">y</a>');

        self::assertStringNotContainsString('javascript:', $result);
        self::assertStringContainsString('href="https://ok.es"', $result);
    }

    public function testKeepsBasicFormatting(): void
    {
        $result = $this->extension()->sanitize('<p><b>negrita</b></p><ul><li>uno</li></ul>');

        self::assertSame('<p><b>negrita</b></p><ul><li>uno</li></ul>', $result);
    }

    public function testNullGivesEmptyString(): void
    {
        self::assertSame('', $this->extension()->sanitize(null));
    }

    public function testFilterIsRegisteredAsSafeHtml(): void
    {
        $filters = $this->extension()->getFilters();

        self::assertSame('sanitize_html', $filters[0]->getName());
        self::assertSame(['html'], $filters[0]->getSafe(new \Twig\Node\Node()));
    }
}
