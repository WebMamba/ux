<?php

namespace Symfony\UX\TwigComponent\Assets;

readonly class ExtractedAsset
{
    public function __construct(
        private string $type,
        private string $content,
        private string $hash
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}