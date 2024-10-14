<?php

namespace Symfony\UX\TwigComponent\Assets;

use Symfony\UX\TwigComponent\MountedComponent;

class TemplateAssetExtractor
{
    public array $extractedAssets = [];

    public function __construct(
        private readonly iterable $extractors,
    ) {}

    public function extract(string $content, MountedComponent $component): array
    {
        $this->extractedAssets = [];
        foreach ($this->extractors as $extractor) {
            if (null !== $extracted = $extractor->extract($content, $component))
            $this->extractedAssets[] = $extracted;
        }

        return $this->extractedAssets;
    }

    public function getExtractedAsset(string $type): ?ExtractedAsset
    {
        foreach ($this->extractedAssets as $extractedAsset) {
            if ($type === $extractedAsset->getType()) {
                return $extractedAsset;
            }
        }

        return null;
    }
}