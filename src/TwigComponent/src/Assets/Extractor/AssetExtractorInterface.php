<?php

namespace Symfony\UX\TwigComponent\Assets\Extractor;

use Symfony\UX\TwigComponent\Assets\ExtractedAsset;
use Symfony\UX\TwigComponent\MountedComponent;

interface AssetExtractorInterface
{
    public function extract(string $content, string $componentName): ?ExtractedAsset;
}