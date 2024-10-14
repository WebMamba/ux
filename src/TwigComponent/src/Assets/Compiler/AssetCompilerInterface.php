<?php

namespace Symfony\UX\TwigComponent\Assets\Compiler;

use Symfony\UX\TwigComponent\Assets\ExtractedAsset;

interface AssetCompilerInterface
{
    public function support(string $type): bool;

    public function compile(ExtractedAsset $extractedAsset): void;
}