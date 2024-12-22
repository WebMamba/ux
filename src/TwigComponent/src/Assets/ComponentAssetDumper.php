<?php

namespace Symfony\UX\TwigComponent\Assets;

use Symfony\Contracts\Cache\CacheInterface;
use Twig\Environment;
use Symfony\UX\TwigComponent\Assets\Compiler\ComponentAssetCompiler;

class ComponentAssetDumper
{
    public function __construct(
        private Environment $twig,
        private TemplateAssetExtractor $assetExtractor,
        private ComponentAssetCompiler $assetCompiler,
        private CacheInterface $cache,
    ) {}

    public function compile(string $componentName, string $template): CompiledComponent
    {
        $template = $this->twig->load(
            $template,
        )->getSourceContext();

        $code = $template->getCode();

        /** @var CompiledComponent $compiledComponent */
        $compiledComponent = $this->cache->get(hash('xxh32', $componentName), function () use ($code, $componentName) {
            $extractedAssets = $this->assetExtractor->extract($code, $componentName);
            $compiledMap = $this->assetCompiler->fromExtractedAssets($extractedAssets);

            return new CompiledComponent($code, $extractedAssets, $compiledMap);
        });

        if ($compiledComponent->getCode() !== $code) {
            $extractedAssets = $this->assetExtractor->extract($code, $componentName);
            $compiledMap = $this->assetCompiler->fromExtractedAssets($extractedAssets);

            $this->cache->delete($componentName);

            $compiledComponent = new CompiledComponent($code, $extractedAssets, $compiledMap);
        }

        return $compiledComponent;
    }
}