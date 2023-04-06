<?php

namespace Symfony\UX\TwigComponent\Twig;

use Twig\Lexer;
use Twig\Source;
use Twig\TokenStream;

class ComponentLexer extends Lexer
{
    const ATTRIBUTES_REGEX = '(?<attributes>(?:\s+[\w\-:.@]+(=(?:\\\"[^\\\"]*\\\"|\'[^\']*\'|[^\'\\\"=<>]+))?)*\s*)';
    const COMPONENTS_REGEX = [
        'open_tags' => '/<\s*([A-Z][\w\-\:\.]+)\s*' . self::ATTRIBUTES_REGEX . '(\s?)+>/',
        'close_tags' => '/<\/\s*([A-Z][\w\-\:\.]+)\s*>/',
        'self_close_tags' => '/<\s*([A-Z][\w\-\:\.]+)\s*' . self::ATTRIBUTES_REGEX . '(\s?)+\/>/',
    ];

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
        $value = $this->lexSelfCloseTag($value);
        $value = $this->lexOpeningTags($value);
        $value = $this->lexClosingTag($value);

        return $value;
    }

    private function lexOpeningTags(string $value)
    {
        return preg_replace_callback(
            self::COMPONENTS_REGEX['open_tags'],
            function (array $matches) {
                $name = lcfirst($matches[1]);
                $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

                return "{% component " . $name . " with " . $attributes . "%}";
            },
            $value

        );
    }

    private function lexClosingTag(string $value)
    {
        return preg_replace(self::COMPONENTS_REGEX['close_tags'], '{% endcomponent %}', $value);
    }

    private function lexSelfCloseTag(string $value)
    {
        return preg_replace_callback(
            self::COMPONENTS_REGEX['self_close_tags'],
            function (array $matches) {
                $name = lcfirst($matches[1]);
                $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

                return "{{ component('" . $name . "', " . $attributes . ") }}";
            },
            $value
        );
    }

    protected function getAttributesFromAttributeString(string $attributeString)
    {
        $attributeString = $this->parseAttributeBag($attributeString);

        $pattern = '/
            (?<attribute>[\w\-:.@]+)
            (
                =
                (?<value>
                    (
                        \"[^\"]+\"
                        |
                        \\\'[^\\\']+\\\'
                        |
                        [^\s>]+
                    )
                )
            )?
        /x';

        if (! preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER)) {
            return '{}';
        }


        $attributes = [];

        foreach ($matches as $match) {
            $attribute = $match['attribute'];
            $value = $match['value'] ?? null;

            if (is_null($value)) {
                $value = 'true';
            }


            if (strpos($attribute, ":") === 0) {
                $attribute = str_replace(":", "", $attribute);
                $value = $this->stripQuotes($value);
            }

            $valueWithoutQuotes = $this->stripQuotes($value);

            if ((strpos($valueWithoutQuotes, '{{') === 0) && (strpos($valueWithoutQuotes, '}}') === strlen($valueWithoutQuotes) - 2)) {
                $value = substr($valueWithoutQuotes, 2, -2);
            } else {
                $value = $value;
            }

            $attributes[$attribute] = $value;
        }

        $out = "{";
        foreach ($attributes as $key => $value) {
            $key = "'$key'";
            $out .= "$key: $value,";
        };

        return rtrim($out, ',') . "}";
    }

    public function stripQuotes(string $value)
    {
        return strpos($value, '"') === 0 || strpos($value, '\'') === 0
            ? substr($value, 1, -1)
            : $value;
    }

    protected function parseAttributeBag(string $attributeString)
    {
        $pattern = "/
            (?:^|\s+)                                        # start of the string or whitespace between attributes
            \{\{\s*(attributes(?:.+?(?<!\s))?)\s*\}\} # exact match of attributes variable being echoed
        /x";

        return preg_replace($pattern, ' :attributes="$1"', $attributeString);
    }
}