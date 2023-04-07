<?php

namespace Symfony\UX\TwigComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class ComponentLexerTest extends KernelTestCase
{
    public function testComponentSyntaxOpenTags(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('tags/open_tag.html.twig');

        $this->assertStringContainsString('propA: 1', $output);
        $this->assertStringContainsString('propB: hello', $output);
    }

    public function testComponentSyntaxSelfCloseTags(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('tags/self_close_tag.html.twig');

        $this->assertStringContainsString('propA: 1', $output);
        $this->assertStringContainsString('propB: hello', $output);
    }

    public function testComponentSyntaxCanRenderEmbeddedComponent(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('tags/embedded_component.html.twig');

        $this->assertStringContainsString('<caption>data table</caption>', $output);
        $this->assertStringContainsString('custom th (key)', $output);
        $this->assertStringContainsString('custom td (1)', $output);
    }
}
