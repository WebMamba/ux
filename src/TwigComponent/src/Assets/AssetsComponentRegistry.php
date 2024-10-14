<?php

namespace Symfony\UX\TwigComponent\Assets;

class AssetsComponentRegistry
{
    private array $componentAssetsMap = [];

    public function add(string $fileName): void
    {
        $this->componentAssetsMap[] = $fileName;
        $this->componentAssetsMap = array_unique($this->componentAssetsMap);
    }

    public function getComponentAssets(): array
    {
        return $this->componentAssetsMap;
    }
}