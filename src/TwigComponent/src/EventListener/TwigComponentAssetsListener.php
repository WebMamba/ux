<?php

namespace Symfony\UX\TwigComponent\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\UX\TwigComponent\Assets\AssetsComponentAttributeFactory;
use Symfony\UX\TwigComponent\Assets\Compiler\ComponentAssetCompiler;
use Symfony\UX\TwigComponent\Assets\TemplateAssetExtractor;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Environment;

class TwigComponentAssetsListener implements EventSubscriberInterface
{
    public function __construct(
        private Environment $twig,
        private TemplateAssetExtractor $assetExtractor,
        private ComponentAssetCompiler $assetCompiler,
        private AssetsComponentAttributeFactory $assetAttributeFactory,
    ) {}
    public static function getSubscribedEvents(): array
    {
        return [
            PreRenderEvent::class => ['onPreRender'],
        ];
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        $template = $this->twig->loadTemplate(
            $this->twig->getTemplateClass($event->getTemplate()),
            $event->getTemplate(),
            $event->getTemplateIndex(),
        )->getSourceContext();

        $component = $event->getMountedComponent();
        $code = $template->getCode();

        $extractedAssets = $this->assetExtractor->extract($code, $component);
        $this->assetCompiler->fromExtractedAssets($extractedAssets);

        $componentAttributes = $this->assetAttributeFactory->create();

        $variables = $event->getVariables();
        $variables['attributes'] = $variables['attributes']->defaults($componentAttributes);

        $event->setVariables($variables);

        if (null === $this->assetExtractor->getExtractedAsset('twig')) {
            $event->setTemplate(
                $this->assetExtractor->getExtractedAsset('twig')->getHash() . '.html.twig'
            );
        }
    }
}