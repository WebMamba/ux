<?php

namespace Symfony\UX\TwigComponent\Assets;

use Symfony\UX\TwigComponent\ComponentAttributes;

class AssetsComponentAttributeFactory
{
    public function __construct(
        private TemplateAssetExtractor $assetExtractor,
    ) {}

    public function create(): ComponentAttributes
    {
        $attributes = [];

        $css = $this->assetExtractor->getExtractedAsset('css');
        if ($css !== null) {
            $attributes['ux-component-style'] =  $css->getHash();
        }

        $js = $this->assetExtractor->getExtractedAsset('js');
        if ($js !== null) {
            $attributes['data-ux-component-id'] =  $js->getHash();
            $attributes['data-controller'] =  $js->getHash();
            $attributes['data-ux-component-controller-files'] = '../../component-assets/' . $js->getHash() . '.js';
        }

        return new ComponentAttributes($attributes);
    }
}