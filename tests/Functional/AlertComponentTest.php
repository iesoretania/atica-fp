<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * El aviso muestra los saltos de línea del texto traducido como saltos reales, sin escribir «<br />» en pantalla
 */
class AlertComponentTest extends KernelTestCase
{
    private function render(string $text): string
    {
        self::bootKernel();
        /** @var Environment $twig */
        $twig = static::getContainer()->get('twig');

        return $twig->createTemplate('<twig:Ui:Alert type="warning" :message="text" />')->render(['text' => $text]);
    }

    public function testLineBreaksAreRenderedAsBreaks(): void
    {
        $html = $this->render("Primera línea.\nSegunda línea.");

        self::assertStringContainsString("Primera línea.<br />\nSegunda línea.", $html);
        self::assertStringNotContainsString('&lt;br', $html);
    }

    public function testTextIsEscapedOnlyOnce(): void
    {
        $html = $this->render("Tom & \"Jerry\"\n<b>x</b>");

        self::assertStringContainsString('Tom &amp; &quot;Jerry&quot;<br />', $html);
        self::assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $html);
        self::assertStringNotContainsString('&amp;amp;', $html);
        self::assertStringNotContainsString('<b>x</b>', $html);
    }
}
