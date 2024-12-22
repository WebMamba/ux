<?php

namespace Symfony\UX\TwigComponent\Assets\Compiler;

use Symfony\UX\TwigComponent\Assets\AssetsComponentRegistry;
use Symfony\UX\TwigComponent\Assets\ExtractedAsset;

class JsCompiler implements AssetCompilerInterface
{
    public function __construct(
        private readonly string $directoryPath,
        private readonly AssetsComponentRegistry $assetsComponentRegistry
    ) {}

    public function support(string $type): bool
    {
        return $type === 'js';
    }

    public function compile(ExtractedAsset $extractedAsset): string
    {
        $fileName = $extractedAsset->getHash().'.js';
        $filePath = $this->directoryPath .'/' . $fileName;

        $this->assetsComponentRegistry->add($fileName);

        if (file_exists($filePath)) {
            return $fileName;
        }

        file_put_contents($filePath, $extractedAsset->getContent(), FILE_APPEND);

        return $fileName;
    }
}