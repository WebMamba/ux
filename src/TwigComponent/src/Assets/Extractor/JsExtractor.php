<?php

namespace Symfony\UX\TwigComponent\Assets\Extractor;

use Symfony\UX\TwigComponent\Assets\ExtractedAsset;
use Symfony\UX\TwigComponent\MountedComponent;

class JsExtractor extends AbstractAssetExtractor
{
    const JS_REGEX = '/<script.*?>(.*?)<\/script>/is';
    public function extract(string $content, MountedComponent $component): ?ExtractedAsset
    {
        $content = $this->extractWithPattern($content, self::JS_REGEX) ?? '';

        if ($content === null) {
            return null;
        }

        return new ExtractedAsset(
            'js',
            $content,
            strtolower($component->getName()).'_'.md5($content)
        );
    }
}