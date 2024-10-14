<?php

namespace Symfony\UX\TwigComponent\Assets\Extractor;

use Symfony\UX\TwigComponent\Assets\ExtractedAsset;
use Symfony\UX\TwigComponent\MountedComponent;

class CssExtractor extends AbstractAssetExtractor
{
    const CSS_REGEX = '/<style.*?>(.*?)<\/style>/is';

    public function extract(string $content, MountedComponent $component): ?ExtractedAsset
    {
        $content = $this->extractWithPattern($content, self::CSS_REGEX) ?? '';

        if ($content === null) {
            return null;
        }

        return new ExtractedAsset(
            'css',
            $content,
            strtolower($component->getName()).'_'.md5($content)
        );
    }
}