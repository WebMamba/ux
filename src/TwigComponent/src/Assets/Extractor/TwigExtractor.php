<?php

namespace Symfony\UX\TwigComponent\Assets\Extractor;

use Symfony\UX\TwigComponent\Assets\ExtractedAsset;
use Symfony\UX\TwigComponent\MountedComponent;

class TwigExtractor implements AssetExtractorInterface
{
    public function extract(string $content, string $componentName): ?ExtractedAsset
    {
        if (!preg_match(CssExtractor::CSS_REGEX, $content) && !preg_match(JsExtractor::JS_REGEX, $content)) {
            return null;
        }

        $content = preg_replace(CssExtractor::CSS_REGEX, '', $content);
        $content = preg_replace(JsExtractor::JS_REGEX, '', $content);

        return new ExtractedAsset(
            'twig',
            $content,
            strtolower($componentName).'_'.md5($content),
        );
    }
}