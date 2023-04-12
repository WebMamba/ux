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

/**
 * Rewrites <twig:component> syntaxes to {% component %} syntaxes.
 */
class TwigPreLexer
{
    private string $input;
    private int $length;
    private int $position = 0;
    private int $line;
    /** @var string[] */
    private array $currentComponents = [];

    public function __construct(int $startingLine = 1)
    {
        $this->line = $startingLine;
    }

    public function preLexComponents(string $input): string
    {
        $this->input = $input;
        $this->length = strlen($input);
        $output = '';

        while ($this->position < $this->length) {
            if ($this->consume('<t:')) {
                $componentName = $this->consumeComponentName();

                if ($componentName === 'block') {
                    $output .= $this->consumeBlock();

                    continue;
                }

                $attributes = $this->consumeAttributes();
                $isSelfClosing = $this->consume('/>');
                if (!$isSelfClosing) {
                    $this->consume('>');
                    $this->currentComponents[] = $componentName;
                }

                $output .= "{% component {$componentName}" . ($attributes ? " with { {$attributes} }" : '') . " %}";
                if ($isSelfClosing) {
                    $output .= '{% endcomponent %}';
                }

                continue;
            }

            if (!empty($this->currentComponents) && $this->check('</t:')) {
                $this->consume('</t:');
                $closingComponentName = $this->consumeComponentName();
                $this->consume('>');

                $lastComponent = array_pop($this->currentComponents);

                if ($closingComponentName !== $lastComponent) {
                    throw new \RuntimeException("Expected closing tag '</t:{$lastComponent}>' but found '</t:{$closingComponentName}>' at line {$this->line}");
                }

                $output .= "{% endcomponent %}";

                continue;
            }

            $char = $this->consumeChar();
            if ($char === "\n") {
                $this->line++;
            }
            $output .= $char;
        }

        return $output;
    }

    private function consumeComponentName(): string
    {
        $start = $this->position;
        while ($this->position < $this->length && preg_match('/[A-Za-z0-9_]/', $this->input[$this->position])) {
            $this->position++;
        }
        $componentName = substr($this->input, $start, $this->position - $start);

        if (empty($componentName)) {
            throw new \RuntimeException("Expected component name at line {$this->line}");
        }

        return $componentName;
    }

    private function consumeAttributes(): string
    {
        $attributes = [];

        while ($this->position < $this->length && !$this->check('>') && !$this->check('/>')) {
            $this->consumeWhitespace();
            if ($this->check('>') || $this->check('/>')) {
                break;
            }

            $isAttributeDynamic = false;

            // :someProp="dynamicVar"
            if ($this->check(':')) {
                $this->consume(':');
                $isAttributeDynamic = true;
            }

            $key = $this->consumeComponentName();

            // <t:component someProp> -> someProp: true
            if (!$this->check('=')) {
                $attributes[] = sprintf("%s: true", $key);
                $this->consumeWhitespace();
                continue;
            }

            $this->expectAndConsumeChar('=');
            $quote = $this->consumeChar(["'", '"']);

            // someProp="{{ dynamicVar }}"
            if ($this->consume('{{')) {
                $this->consumeWhitespace();
                $attributeValue = rtrim($this->consumeUntil('}'));
                $this->expectAndConsumeChar('}');
                $this->expectAndConsumeChar('}');
                $this->consumeUntil($quote);
                $isAttributeDynamic = true;
            } else {
                $attributeValue = $this->consumeUntil($quote);
            }
            $this->expectAndConsumeChar($quote);

            if ($isAttributeDynamic) {
                $attributes[] = sprintf("%s: %s", $key, $attributeValue);
            } else {
                $attributes[] = sprintf("%s: '%s'", $key, str_replace("'", "\'", $attributeValue));
            }

            $this->consumeWhitespace();
        }

        return implode(', ', $attributes);
    }

    private function consume(string $string): bool
    {
        if (substr($this->input, $this->position, strlen($string)) === $string) {
            $this->position += strlen($string);
            return true;
        }

        return false;
    }

    private function consumeChar($validChars = null): string
    {
        if ($this->position >= $this->length) {
            throw new \RuntimeException("Unexpected end of input");
        }

        $char = $this->input[$this->position];

        if ($validChars !== null && !in_array($char, (array)$validChars, true)) {
            throw new \RuntimeException("Expected one of [" . implode('', (array)$validChars) . "] but found '{$char}' at line {$this->line}");
        }

        $this->position++;

        return $char;
    }

    private function consumeUntil(string $endString): string
    {
        $start = $this->position;
        $endCharLength = strlen($endString);

        while ($this->position < $this->length) {
            if (substr($this->input, $this->position, $endCharLength) === $endString) {
                break;
            }

            if ($this->input[$this->position] === "\n") {
                $this->line++;
            }
            $this->position++;
        }

        return substr($this->input, $start, $this->position - $start);
    }

    private function consumeWhitespace(): void
    {
        while ($this->position < $this->length && preg_match('/\s/', $this->input[$this->position])) {
            if ($this->input[$this->position] === "\n") {
                $this->line++;
            }
            $this->position++;
        }
    }

    /**
     * Checks that the next character is the one given and consumes it.
     */
    private function expectAndConsumeChar(string $char): void
    {
        if (strlen($char) !== 1) {
            throw new \InvalidArgumentException('Expected a single character');
        }

        if ($this->position >= $this->length || $this->input[$this->position] !== $char) {
            throw new \RuntimeException("Expected '{$char}' but found '{$this->input[$this->position]}' at line {$this->line}");
        }
        $this->position++;
    }

    private function check(string $chars): bool
    {
        $charsLength = strlen($chars);
        if ($this->position + $charsLength > $this->length) {
            return false;
        }

        for ($i = 0; $i < $charsLength; $i++) {
            if ($this->input[$this->position + $i] !== $chars[$i]) {
                return false;
            }
        }

        return true;
    }

    private function consumeBlock(): string
    {
        $attributes = $this->consumeAttributes();
        $this->consume('>');

        $blockName = '';
        foreach (explode(', ', $attributes) as $attr) {
            list($key, $value) = explode(': ', $attr);
            if ($key === 'name') {
                $blockName = trim($value, "'");
                break;
            }
        }

        if (empty($blockName)) {
            throw new \RuntimeException("Expected block name at line {$this->line}");
        }

        $output = "{% block {$blockName} %}";

        $closingTag = "</t:block>";
        if (!$this->doesStringEventuallyExist($closingTag)) {
            throw new \RuntimeException("Expected closing tag '{$closingTag}' for block '{$blockName}' at line {$this->line}");
        }
        $blockContents = $this->consumeUntil($closingTag);

        $subLexer = new self($this->line);
        $output .= $subLexer->preLexComponents($blockContents);

        $this->consume($closingTag);
        $output .= "{% endblock %}";

        return $output;
    }

    private function doesStringEventuallyExist(string $needle): bool
    {
        $remainingString = substr($this->input, $this->position);

        return str_contains($remainingString, $needle);
    }
}

