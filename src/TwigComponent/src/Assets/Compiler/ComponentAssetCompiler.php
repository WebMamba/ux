<?php

namespace Symfony\UX\TwigComponent\Assets\Compiler;

use Symfony\UX\TwigComponent\Assets\ExtractedAsset;

class ComponentAssetCompiler
{
    /**
     * @param iterable<AssetCompilerInterface> $assetCompilers
     */
    public function __construct(
        private readonly iterable $assetCompilers,
    ) {}

    /**
     * @param ExtractedAsset[] $extractedAssets
     */
    public function fromExtractedAssets(array $extractedAssets): void
    {
        foreach ($extractedAssets as $extractedAsset) {
            foreach ($this->assetCompilers as $assetCompiler) {
                if ($assetCompiler->support($extractedAsset->getType())) {
                    $assetCompiler->compile($extractedAsset);
                }
            }
        }
    }
}