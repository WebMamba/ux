<?php

namespace Symfony\UX\TwigComponent\Assets\Compiler;

use Symfony\UX\TwigComponent\Assets\AssetsComponentRegistry;
use Symfony\UX\TwigComponent\Assets\ExtractedAsset;

class CssCompiler implements AssetCompilerInterface
{
    const CSS_ATTRIBUTE_ID = 'ux-component-style';
    
    public function __construct(
        private readonly string $directoryPath,
        private readonly AssetsComponentRegistry $assetsComponentRegistry
    ) {}

    public function support(string $type): bool
    {
        return 'css' === $type;
    }

    public function compile(ExtractedAsset $extractedAsset): string
    {
        $contentTemplate = <<<EOF
%s {
    %s
}
EOF;

        $fileName = $extractedAsset->getHash().'.css';
        $filePath = $this->directoryPath .'/' . $fileName;

        $this->assetsComponentRegistry->add($fileName);

        if (file_exists($filePath)) {
            return $fileName;
        }
        
        $attributes = '['.self::CSS_ATTRIBUTE_ID.'='.$extractedAsset->getHash().']';

        $contentFile = sprintf($contentTemplate, $attributes, $extractedAsset->getContent());

        file_put_contents($filePath, $contentFile, FILE_APPEND);

        return $fileName;
    }
}