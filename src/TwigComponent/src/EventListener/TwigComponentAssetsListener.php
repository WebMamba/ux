<?php

namespace Symfony\UX\TwigComponent\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\UX\TwigComponent\Assets\AssetsComponentAttributeFactory;
use Symfony\UX\TwigComponent\Assets\AssetsComponentRegistry;
use Symfony\UX\TwigComponent\Assets\ComponentAssetDumper;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

class TwigComponentAssetsListener implements EventSubscriberInterface
{
    public function __construct(
        private ComponentAssetDumper $assetCompiler,
        private AssetsComponentAttributeFactory $assetAttributeFactory,
        private AssetsComponentRegistry $assetRegistry,
    ) {}
    public static function getSubscribedEvents(): array
    {
        return [
            PreRenderEvent::class => ['onPreRender'],
        ];
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        $compiledComponent = $this->assetCompiler->compile($event->getMountedComponent()->getName(), $event->getTemplate());

        $componentAttributes = $this->assetAttributeFactory->create($compiledComponent);

        $variables = $event->getVariables();
        $variables['attributes'] = $variables['attributes']->defaults($componentAttributes);

        $event->setVariables($variables);

        if ([] !== $compiledComponent->getExtractedAssets()) {
            $event->setTemplate(
                $compiledComponent->getCompiledAssets()['twig']
            );
        }

        foreach ($compiledComponent->getCompiledAssets() as $compiledAsset) {
            $this->assetRegistry->add($compiledAsset);
        }
    }
}