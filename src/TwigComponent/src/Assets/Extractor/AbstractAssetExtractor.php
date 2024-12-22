<?php

namespace Symfony\UX\TwigComponent\Assets\Extractor;

use Symfony\UX\TwigComponent\Assets\ExtractedAsset;
use Symfony\UX\TwigComponent\MountedComponent;

abstract class AbstractAssetExtractor implements AssetExtractorInterface
{
    abstract public function extract(string $content, string $componentName): ?ExtractedAsset;

    protected function extractWithPattern(string $content, string $pattern): ?string
    {
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        if (isset($matches[0][1])) {
            return $matches[0][1];
        }

        return null;
    }
}