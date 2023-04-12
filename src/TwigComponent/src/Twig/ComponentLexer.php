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
        $preparsed = $this->preparsed($source->getCode());

        return parent::tokenize(
            new Source(
                $preparsed,
                $source->getName(),
                $source->getPath()
            )
        );
    }

    private function preparsed(string $value)
    {
        $value = $this->lexBlockTags($value);
        $value = $this->lexBlockTagsClose($value);
        $value = $this->lexSelfCloseTag($value);
        $value = $this->lexOpeningTags($value);
        $value = $this->lexClosingTag($value);

        return $value;
    }

    private function lexOpeningTags(string $value)
    {
        return preg_replace_callback(
            self::OPEN_TAGS_REGEX,
            function (array $matches) {
                $name = $matches['name'];
                $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

                return '{% component '.$name.' with '.$attributes.'%}';
            },
            $value
        );
    }

    private function lexClosingTag(string $value)
    {
        return preg_replace(self::CLOSE_TAGS_REGEX, '{% endcomponent %}', $value);
    }

    private function lexSelfCloseTag(string $value)
    {
        return preg_replace_callback(
            self::SELF_CLOSE_TAGS_REGEX,
            function (array $matches) {
                $name = $matches['name'];
                $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

                return "{{ component('".$name."', ".$attributes.') }}';
            },
            $value
        );
    }

    private function lexBlockTags(string $value)
    {
        return preg_replace_callback(
            self::BLOCK_TAGS_OPEN,
            function (array $matches) {
                $name = $matches['name'];

                return '{% block '.$name.' %}';
            },
            $value
        );
    }

    private function lexBlockTagsClose(string $value)
    {
        return preg_replace(
            self::BLOCK_TAGS_CLOSE,
            '{% endblock %}',
            $value
        );
    }

    protected function getAttributesFromAttributeString(string $attributeString)
    {
        $attributeString = $this->parseAttributeBag($attributeString);

        if (!preg_match_all(self::ATTRIBUTE_KEY_VALUE_REGEX, $attributeString, $matches, \PREG_SET_ORDER)) {
            return '{}';
        }

        $attributes = [];
        foreach ($matches as $match) {
            $attribute = $match['attribute'];
            $value = $match['value'] ?? null;

            if (null === $value) {
                $value = 'true';
            }

            if (str_starts_with($attribute, ':')) {
                $attribute = str_replace(':', '', $attribute);
                $value = $this->stripQuotes($value);
            }

            $valueWithoutQuotes = $this->stripQuotes($value);

            if (str_starts_with($valueWithoutQuotes, '{{') && (strpos($valueWithoutQuotes, '}}') === \strlen($valueWithoutQuotes) - 2)) {
                $value = substr($valueWithoutQuotes, 2, -2);
            } else {
                $value = $value;
            }

            $attributes[$attribute] = $value;
        }

        $out = '{';
        foreach ($attributes as $key => $value) {
            $key = "'$key'";
            $out .= "$key: $value,";
        }

        return rtrim($out, ',').'}';
    }

    public function stripQuotes(string $value)
    {
        return str_starts_with($value, '"') || str_starts_with($value, '\'')
            ? substr($value, 1, -1)
            : $value;
    }

    protected function parseAttributeBag(string $attributeString)
    {
        return preg_replace(self::ATTRIBUTE_BAG_REGEX, ' :attributes="$1"', $attributeString);
    }
}
