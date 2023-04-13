<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\Twig\TwigPreLexer;

final class TwigPreLexerTest extends TestCase
{
    /**
     * @dataProvider getLexTests
     */
    public function testPreLex(string $input, string $expectedOutput): void
    {
        $lexer = new TwigPreLexer();
        $this->assertSame($expectedOutput, $lexer->preLexComponents($input));
    }

    public function getLexTests(): iterable
    {
        yield 'simple_component' => [
            '<t:foo />',
            '{% component foo %}{% endcomponent %}',
        ];

        yield 'component_with_attributes' => [
            '<t:foo bar="baz" with_quotes="It\'s with quotes" />',
            "{% component foo with { bar: 'baz', with_quotes: 'It\'s with quotes' } %}{% endcomponent %}",
        ];

        yield 'component_with_dynamic_attributes' => [
            '<t:foo dynamic="{{ dynamicVar }}" :otherDynamic="anotherVar" />',
            '{% component foo with { dynamic: dynamicVar, otherDynamic: anotherVar } %}{% endcomponent %}',
        ];

        yield 'component_with_closing_tag' => [
            '<t:foo></t:foo>',
            '{% component foo %}{% endcomponent %}',
        ];

        yield 'component_with_block' => [
            '<t:foo><t:block name="foo_block">Foo</t:block></t:foo>',
            '{% component foo %}{% block foo_block %}Foo{% endblock %}{% endcomponent %}',
        ];

        yield 'component_with_embedded_component_inside_block' => [
            '<t:foo><t:block name="foo_block"><t:bar /></t:block></t:foo>',
            '{% component foo %}{% block foo_block %}{% component bar %}{% endcomponent %}{% endblock %}{% endcomponent %}',
        ];

        yield 'attribute_with_no_value' => [
            '<t:foo bar />',
            '{% component foo with { bar: true } %}{% endcomponent %}',
        ];

        yield 'component_with_default_block_content' => [
            '<t:foo>Foo</t:foo>',
            '{% component foo %}{% block default %}Foo{% endblock %}{% endcomponent %}',
        ];

        yield 'component_with_default_block_that_holds_a_component_and_multi_blocks' => [
            '<t:foo>Foo <t:bar /><t:block name="other_block">Other block</t:block></t:foo>',
            '{% component foo %}{% block default %}Foo {% component bar %}{% endcomponent %}{% endblock %}{% block other_block %}Other block{% endblock %}{% endcomponent %}',
        ];
    }
}
