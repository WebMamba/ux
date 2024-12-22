<?php

namespace Symfony\UX\TwigComponent\Assets;

class CompiledComponent
{
    public function __construct(
        private string $code,
        private readonly array $extractedAssets = [],
        private readonly array $compiledAssetsMap = [],
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getExtractedAssets(): array
    {
        return $this->extractedAssets;
    }

    public function getCompiledAssets(): array
    {
        return $this->compiledAssetsMap;
    }
}