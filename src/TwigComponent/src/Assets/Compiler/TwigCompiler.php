<?php

namespace Symfony\UX\TwigComponent\Assets\Compiler;

use Symfony\UX\TwigComponent\Assets\ExtractedAsset;

class TwigCompiler implements AssetCompilerInterface
{
    public function __construct(
        private readonly string $directoryPath,
    ) {}

    public function support(string $type): bool
    {
        return $type === 'twig';
    }

    public function compile(ExtractedAsset $extractedAsset): void
    {
        $fileName = $extractedAsset->getHash().'.html.twig';
        $filePath = $this->directoryPath .'/' . $fileName;

        file_put_contents($filePath, $extractedAsset->getContent());
    }
}