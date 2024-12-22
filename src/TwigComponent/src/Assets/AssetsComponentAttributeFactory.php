<?php

namespace Symfony\UX\TwigComponent\Assets;

use Symfony\UX\TwigComponent\ComponentAttributes;

class AssetsComponentAttributeFactory
{
    public function create(CompiledComponent $compiledComponent): ComponentAttributes
    {
        $attributes = [];

        foreach ($compiledComponent->getExtractedAssets() as $asset) {
            switch ($asset->getType()) {
                case 'css': {
                    $attributes['ux-component-style'] =  $asset->getHash();
                }
                case 'js': {
                    $attributes['data-ux-component-id'] =  $asset->getHash();
                    $attributes['data-controller'] =  $asset->getHash();
                    $attributes['data-ux-component-controller-files'] = '../../component-assets/' . $asset->getHash() . '.js';
                }
            }
        }

        return new ComponentAttributes($attributes);
    }

}