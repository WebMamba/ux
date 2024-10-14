<?php

namespace Symfony\UX\TwigComponent\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
class TwigComponentDevAssetsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $cssCompiledFilePath,
    ) {}

    public function resolveComponentAssetsFiles(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $pathInfo = $event->getRequest()->getPathInfo();

        if (!str_starts_with($pathInfo, '/component-assets/')) {
            return;
        }

        $fileName = str_replace('/component-assets', '', $pathInfo);
        $file = file_get_contents($this->cssCompiledFilePath . $fileName);

        if (!$file) {
            return;
        }

        $mediaType = '';
        if (str_ends_with($fileName, '.css')) {
            $mediaType = 'text/css';
        }

        if (str_ends_with($fileName, '.js')) {
            $mediaType = 'text/javascript';
        }

        $response = new BinaryFileResponse($this->cssCompiledFilePath . $fileName, autoLastModified: false);
        $response
            ->setPublic()
            ->setMaxAge(604800) // 1 week
            ->setImmutable()
            ->setEtag(hash('md5', $file))
            ->headers->set('Content-Type', $mediaType);
        ;

        $response->headers->set('X-Assets-Dev', true);

        $event->setResponse($response);
        $event->stopPropagation();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['resolveComponentAssetsFiles', 40]],
        ];
    }
}