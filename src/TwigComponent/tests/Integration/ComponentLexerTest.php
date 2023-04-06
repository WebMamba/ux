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

    public function testComponentSyntaxSelfClosingTags(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('tags/self_closing_tag.html.twig');

        $this->assertStringContainsString('propA: 1', $output);
        $this->assertStringContainsString('propB: hello', $output);
    }
}