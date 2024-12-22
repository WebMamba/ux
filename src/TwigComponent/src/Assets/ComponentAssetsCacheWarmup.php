<?php

namespace Symfony\UX\TwigComponent\Assets;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ComponentAssetsCacheWarmup implements CacheWarmerInterface
{
    public function __construct(
        private string              $twigTemplatesPath,
        private ComponentFactory    $componentFactory,
        private readonly array      $componentClassMap,
        private ?string             $anonymousDirectory = null,
        public ComponentAssetDumper $compiler,
        private Environment $twig,
    ) {}

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $components = $this->findComponents();

        foreach ($components as $component) {
            if ($component->getName() === 'UX:Icon') {
                continue;
            }

            $this->compiler->compile($component->getName(), $component->getTemplate());
        }

        return [];
    }

    /**
     * @return array<string, ComponentMetadata>
     */
    private function findComponents(): array
    {
        $components = [];
        foreach ($this->componentClassMap as $class => $name) {
            $components[$name] ??= $this->componentFactory->metadataFor($name);
        }
        foreach ($this->findAnonymousComponents() as $name => $template) {
            $components[$name] ??= $this->componentFactory->metadataFor($name);
        }

        return $components;
    }

    /**
     * Return a map of component name => template.
     *
     * @return array<string, string>
     */
    private function findAnonymousComponents(): array
    {
        $componentsDir = $this->twigTemplatesPath.'/'.$this->anonymousDirectory;
        $dirs = [$componentsDir => FilesystemLoader::MAIN_NAMESPACE];
        $twigLoader = $this->twig->getLoader();
        if ($twigLoader instanceof FilesystemLoader) {
            foreach ($twigLoader->getNamespaces() as $namespace) {
                if (str_starts_with($namespace, '!')) {
                    continue; // ignore parent convention namespaces
                }

                foreach ($twigLoader->getPaths($namespace) as $path) {
                    if (FilesystemLoader::MAIN_NAMESPACE === $namespace) {
                        $componentsDir = $path.'/'.$this->anonymousDirectory;
                    } else {
                        $componentsDir = $path.'/components';
                    }

                    if (!is_dir($componentsDir)) {
                        continue;
                    }

                    $dirs[$componentsDir] = $namespace;
                }
            }
        }

        $components = [];
        $finderTemplates = new Finder();
        $finderTemplates->files()
            ->in(array_keys($dirs))
            ->notPath('/_')
            ->name('*.html.twig')
        ;
        foreach ($finderTemplates as $template) {
            $component = str_replace('/', ':', $template->getRelativePathname());
            $component = substr($component, 0, -10); // remove file extension ".html.twig"
            $path = $template->getPath();

            if ($template->getRelativePath()) {
                $path = \rtrim(\substr($template->getPath(), 0, -1 * \strlen($template->getRelativePath())), '/');
            }

            if (isset($dirs[$path]) && FilesystemLoader::MAIN_NAMESPACE !== $dirs[$path]) {
                $component = $dirs[$path].':'.$component;
            }

            $components[$component] = $component;
        }

        return $components;
    }
}