<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

use Twig\Lexer;
use Twig\Source;
use Twig\TokenStream;

/**
 * @author Mathèo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 *
 * thanks to @giorgiopogliani for the inspiration on this lexer <3
 *
 * @see https://github.com/giorgiopogliani/twig-components
 */
class ComponentLexer extends Lexer
{
    public const ATTRIBUTES_REGEX = '(?<attributes>(?:\s+[\w\-:.@]+(=(?:"[^"]*"|\'[^\']*\'|[^\'\\\"=<>]+))?)*\s*)';
    public const OPEN_TAGS_REGEX = '/<\s*t:(?<name>([[\w\-\:\.]+))\s*'.self::ATTRIBUTES_REGEX.'(\s?)+>/';
    public const CLOSE_TAGS_REGEX = '/<\/\s*t:([\w\-\:\.]+)\s*>/';
    public const SELF_CLOSE_TAGS_REGEX = '/<\s*t:(?<name>([\w\-\:\.]+))\s*'.self::ATTRIBUTES_REGEX.'(\s?)+\/>/';
    public const BLOCK_TAGS_OPEN = '/<\s*t:block\s+name=("|\')(?<name>([\w\-\:\.]+))("|\')\s*>/';
    public const BLOCK_TAGS_CLOSE = '/<\s*\/\s*t:block\s*>/';
    public const ATTRIBUTE_BAG_REGEX = '/(?:^|\s+)\{\{\s*(attributes(?:.+?(?<!\s))?)\s*\}\}/x';
    public const ATTRIBUTE_KEY_VALUE_REGEX = '/(?<attribute>[\w\-:.@]+)(=(?<value>(\"[^\"]+\"|\\\'[^\\\']+\\\'|[^\s>]+)))?/x';
    
    public function tokenize(Source $source): TokenStream
    {
        $preLexer = new TwigPreLexer();
        $preparsed = $preLexer->preLexComponents($source->getCode());

        return parent::tokenize(
            new Source(
                $preparsed,
                $source->getName(),
                $source->getPath()
            )
        );
    }
}
