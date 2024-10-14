<?php

namespace Symfony\UX\TwigComponent\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\UX\TwigComponent\Assets\AssetsComponentRegistry;

class TwigComponentImportMapListener implements EventSubscriberInterface
{
    public function __construct(
        private AssetsComponentRegistry $assetsComponentRegistry,
    ) {}

    public function generateLinks(ResponseEvent $responseEvent): void
    {
        if (empty($this->assetsComponentRegistry->getComponentAssets())) {
            return;
        }

        $html = $responseEvent->getResponse()->getContent();

        $newContent = '';
        foreach ($this->assetsComponentRegistry->getComponentAssets() as $componentAsset) {
            $assetPath = '/component-assets/' . $componentAsset;

            if (str_ends_with($componentAsset, '.css')) {
                $newContent .= '<link rel="stylesheet" href="' . $assetPath. '"/>' . PHP_EOL;
            }

            if (str_ends_with($componentAsset, '.js')) {
                $newContent .= '<link rel="modulepreload" href="' . $assetPath . '"/>' . PHP_EOL;
            }
        }

        $newContent = preg_replace('/<\/head\s*.*?>/i', $newContent . '<head/>', $html);

        $responseEvent->getResponse()->setContent($newContent);
    }

    public function generateImportMap(ResponseEvent $event): void
    {
        if (empty($this->assetsComponentRegistry->getComponentAssets())) {
            return;
        }

        $html = $event->getResponse()->getContent();

        preg_match_all('/<script.*?type="importmap".*?>(.*?)<\/script>/is', $html, $matches, PREG_SET_ORDER);

        $initialImportMap = $matches[0][1];
        $importMap = json_decode($matches[0][1], true);

        foreach ($this->assetsComponentRegistry as $componentAsset) {
            $assetPath = '/component-assets/' . $componentAsset;

            if (str_ends_with($componentAsset, '.js')) {
                $importMap['imports'][$componentAsset] = $assetPath;
            }
        }

        $newImportMap = json_encode($importMap, JSON_PRETTY_PRINT);
        $newImportMap = $this->removeFirstAndLastCurlyBrace($newImportMap);

        $newContent = preg_replace($initialImportMap, $newImportMap, $html);

        $event->getResponse()->setContent($newContent);
    }

    private function removeFirstAndLastCurlyBrace($str) {
        $firstPos = strpos($str, '{');

        $lastPos = strrpos($str, '}');

        // If both first and last positions are valid
        if ($firstPos !== false && $lastPos !== false && $firstPos != $lastPos) {
            // Remove the first occurrence
            $str = substr_replace($str, '', $firstPos, 1);

            // After removing the first, the position of the last shifts by 1
            $lastPos--;

            // Remove the last occurrence
            $str = substr_replace($str, '', $lastPos, 1);
        }

        return $str;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['generateLinks'],
                ['generateImportMap'],
            ],
        ];
    }
}