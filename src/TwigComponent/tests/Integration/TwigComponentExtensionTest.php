<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * @author Mathèo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
class TwigComponentExtensionTest extends KernelTestCase
{
    public function testComponentSyntaxOpenTags(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('tags/open_tag.html.twig');

        $this->assertStringContainsString('propA: 1', $output);
        $this->assertStringContainsString('propB: hello', $output);
    }

    public function testRenderTwigComponentWithSlot(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_with_default_slot.html.twig');

        $this->assertStringContainsString('You have a new message!', $output);
    }

    public function testRenderTwigComponentWithAttributes(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_with_attributes.html.twig');

        $this->assertStringContainsString('You have a new message!', $output);
        $this->assertStringContainsString('background: red', $output);
    }

    public function testRenderTwigComponentWithCustomSlot(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_with_custom_slot.html.twig');

        $this->assertStringContainsString('You have a new message!', $output);
        $this->assertStringContainsString('background: red', $output);
        $this->assertStringContainsString('from @fapbot', $output);
    }

    public function testRenderStaticTwigComponent(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_static_components.html.twig');

        $this->assertStringContainsString('Submit!', $output);
    }

    public function testRenderStaticTwigComponentWithAttributes(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/use_attribute_variables.html.twig');

        $this->assertStringContainsString('Submit!', $output);
        $this->assertStringContainsString('class="btn-primary btn"', $output);
    }

    public function testRenderStaticComponentInSubFolder(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_static_component_in_sub_folder.html.twig');

        $this->assertStringContainsString('Hello from a sub folder', $output);
    }
}
