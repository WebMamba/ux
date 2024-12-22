<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\TwigComponent\Assets\AssetMapperCssCompiler;
use Symfony\UX\TwigComponent\Assets\AssetsComponentAttributeFactory;
use Symfony\UX\TwigComponent\Assets\AssetsComponentRegistry;
use Symfony\UX\TwigComponent\Assets\Compiler\ComponentAssetCompiler;
use Symfony\UX\TwigComponent\Assets\Compiler\CssCompiler;
use Symfony\UX\TwigComponent\Assets\Compiler\JsCompiler;
use Symfony\UX\TwigComponent\Assets\Compiler\TwigCompiler;
use Symfony\UX\TwigComponent\Assets\ComponentAssetDumper;
use Symfony\UX\TwigComponent\Assets\ComponentAssetsCacheWarmup;
use Symfony\UX\TwigComponent\Assets\ComponentsAssetsExtractor;
use Symfony\UX\TwigComponent\Assets\Extractor\CssExtractor;
use Symfony\UX\TwigComponent\Assets\Extractor\JsExtractor;
use Symfony\UX\TwigComponent\Assets\Extractor\TwigExtractor;
use Symfony\UX\TwigComponent\Assets\TemplateAssetExtractor;
use Symfony\UX\TwigComponent\Assets\TemplateLoader;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Command\TwigComponentDebugCommand;
use Symfony\UX\TwigComponent\ComponentCssPublicPathResolver;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\ComponentRendererInterface;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Symfony\UX\TwigComponent\DependencyInjection\Compiler\TwigComponentPass;
use Symfony\UX\TwigComponent\EventListener\TwigComponentAssetsListener;
use Symfony\UX\TwigComponent\EventListener\TwigComponentDevAssetsSubscriber;
use Symfony\UX\TwigComponent\EventListener\TwigComponentImportMapListener;
use Symfony\UX\TwigComponent\Twig\ComponentExtension;
use Symfony\UX\TwigComponent\Twig\ComponentLexer;
use Symfony\UX\TwigComponent\Twig\ComponentRuntime;
use Symfony\UX\TwigComponent\Twig\TwigEnvironmentConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class TwigComponentExtension extends Extension implements ConfigurationInterface
{
    private const DEPRECATED_DEFAULT_KEY = '__deprecated__use_old_naming_behavior';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        if (!isset($container->getParameter('kernel.bundles')['TwigBundle'])) {
            throw new LogicException('The TwigBundle is not registered in your application. Try running "composer require symfony/twig-bundle".');
        }

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $defaults = $config['defaults'];
        if ($defaults === [self::DEPRECATED_DEFAULT_KEY]) {
            trigger_deprecation('symfony/ux-twig-component', '2.13', 'Not setting the "twig_component.defaults" config option is deprecated. Check the documentation for an example configuration.');
            $container->setParameter('ux.twig_component.legacy_autonaming', true);

            $defaults = [];
        }
        $container->setParameter('ux.twig_component.component_defaults', $defaults);

        $container->register('ux.twig_component.component_template_finder', ComponentTemplateFinder::class)
            ->setArguments([
                new Reference('twig.loader'),
                $config['anonymous_template_directory'],
            ]);
        $container->setAlias(ComponentRendererInterface::class, 'ux.twig_component.component_renderer');

        $container->registerAttributeForAutoconfiguration(
            AsTwigComponent::class,
            static function (ChildDefinition $definition, AsTwigComponent $attribute) {
                $definition->addTag('twig.component', array_filter($attribute->serviceConfig()));
            }
        );

        $container->register('ux.twig_component.component_factory', ComponentFactory::class)
            ->setArguments([
                new Reference('ux.twig_component.component_template_finder'),
                class_exists(AbstractArgument::class) ? new AbstractArgument(\sprintf('Added in %s.', TwigComponentPass::class)) : null,
                new Reference('property_accessor'),
                new Reference('event_dispatcher'),
                class_exists(AbstractArgument::class) ? new AbstractArgument(\sprintf('Added in %s.', TwigComponentPass::class)) : [],
            ])
        ;

        $container->register('ux.twig_component.component_stack', ComponentStack::class);

        $container->register('ux.twig_component.component_renderer', ComponentRenderer::class)
            ->setArguments([
                new Reference('twig'),
                new Reference('event_dispatcher'),
                new Reference('ux.twig_component.component_factory'),
                new Reference('property_accessor'),
                new Reference('ux.twig_component.component_stack'),
            ])
        ;

        $container->register('ux.twig_component.twig.component_extension', ComponentExtension::class)
            ->addTag('twig.extension')
        ;

        $container->register('.ux.twig_component.twig.component_runtime', ComponentRuntime::class)
            ->setArguments([
                new Reference('ux.twig_component.component_renderer'),
            ])
            ->addTag('twig.runtime')
        ;

        $container->register('ux.twig_component.twig.lexer', ComponentLexer::class);

        $container->register('ux.twig_component.twig.environment_configurator', TwigEnvironmentConfigurator::class)
            ->setDecoratedService(new Reference('twig.configurator.environment'))
            ->setArguments([new Reference('ux.twig_component.twig.environment_configurator.inner')]);

        $container->register('ux.twig_component.command.debug', TwigComponentDebugCommand::class)
            ->setArguments([
                new Parameter('twig.default_path'),
                new Reference('ux.twig_component.component_factory'),
                new Reference('twig'),
                class_exists(AbstractArgument::class) ? new AbstractArgument(\sprintf('Added in %s.', TwigComponentPass::class)) : [],
                $config['anonymous_template_directory'],
            ])
            ->addTag('console.command')
        ;

        $container->register('ux_twig_component.css_compiler', CssCompiler::class)
            ->setArguments([
                $config['component_css_generated_path_file'],
                new Reference('ux_twig_component.assets_component_registry')
            ])
            ->addTag('twig.component.compiler')
        ;

        $container->register('ux_twig_component.js_compiler', JsCompiler::class)
            ->setArguments([
                $config['component_css_generated_path_file'],
                new Reference('ux_twig_component.assets_component_registry')
            ])
            ->addTag('twig.component.compiler')
        ;

        $container->register('ux_twig_component.twig_compiler', TwigCompiler::class)
            ->setArguments([
                $config['component_css_generated_path_file'],
            ])
            ->addTag('twig.component.compiler')
        ;

        $container->register('ux_twig_component.asset_compiler', ComponentAssetCompiler::class)
            ->setArguments([
                tagged_iterator('twig.component.compiler')
            ])
        ;

        $container->register('ux_twig_component.assets_component_registry', AssetsComponentRegistry::class);

        $container->register('ux_twig_component.dev_assets_subscriber', TwigComponentDevAssetsSubscriber::class)
            ->setArguments([
                $config['component_css_generated_path_file'],
            ])
            ->addTag('kernel.event_subscriber')
        ;

        $container->register('ux_twig_component.import_map_listener', TwigComponentImportMapListener::class)
            ->setArguments([
                new Reference('ux_twig_component.assets_component_registry')
            ])
            ->addTag('kernel.event_subscriber')
        ;

        $container->register('ux_twig_component.extractor.css', CssExtractor::class)
            ->addTag('twig.component.extractor')
        ;

        $container->register('ux_twig_component.extractor.js', JsExtractor::class)
            ->addTag('twig.component.extractor')
        ;

        $container->register('ux_twig_component.extractor.twig', TwigExtractor::class)
            ->addTag('twig.component.extractor')
        ;

        $container->register('ux_twig_component.extractor.template_extractor', TemplateAssetExtractor::class)
            ->setArguments([
                tagged_iterator('twig.component.extractor')
            ])
        ;

        $container->register('ux_twig_component.assets_components_attributes_factory', AssetsComponentAttributeFactory::class);

        $container->register('ux_twig_component.assets_component_compiler', ComponentAssetDumper::class)
            ->setArguments([
                new Reference('twig'),
                new Reference('ux_twig_component.extractor.template_extractor'),
                new Reference('ux_twig_component.asset_compiler'),
                new Reference('cache.system')
            ])
        ;

        $container->register('ux_twig_component.assets_cache_warmup', ComponentAssetsCacheWarmup::class)
            ->setArguments([
                new Parameter('twig.default_path'),
                new Reference('ux.twig_component.component_factory'),
                class_exists(AbstractArgument::class) ? new AbstractArgument(\sprintf('Added in %s.', TwigComponentPass::class)) : [],
                $config['anonymous_template_directory'],
                new Reference('ux_twig_component.assets_component_compiler'),
                new Reference('twig')
            ])
            ->addTag('kernel.cache_warmer')
        ;

        $container->register('ux_twig_component.assets_listener', TwigComponentAssetsListener::class)
            ->setArguments([
                new Reference('ux_twig_component.assets_component_compiler'),
                new Reference('ux_twig_component.assets_components_attributes_factory'),
                new Reference('ux_twig_component.assets_component_registry')
            ])
            ->addTag('kernel.event_subscriber')
        ;

        $container->setAlias('console.command.stimulus_component_debug', 'ux.twig_component.command.debug')
            ->setDeprecated('symfony/ux-twig-component', '2.13', '%alias_id%');

        if ($container->getParameter('kernel.debug') && $config['profiler']) {
            $loader->load('debug.php');
        }
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('twig_component');
        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->validate()
            ->always(function ($v) {
                if (!isset($v['anonymous_template_directory'])) {
                    trigger_deprecation('symfony/twig-component-bundle', '2.13', 'Not setting the "twig_component.anonymous_template_directory" config option is deprecated. It will default to "components" in 3.0.');
                    $v['anonymous_template_directory'] = null;
                }

                return $v;
            })
            ->end()
            ->children()
                ->arrayNode('defaults')
                    ->defaultValue([self::DEPRECATED_DEFAULT_KEY])
                    ->useAttributeAsKey('namespace')
                    ->validate()
                        ->always(function ($v) {
                            foreach ($v as $namespace => $defaults) {
                                if (!str_ends_with($namespace, '\\')) {
                                    throw new InvalidConfigurationException(\sprintf('The twig_component.defaults namespace "%s" is invalid: it must end in a "\".', $namespace));
                                }
                            }

                            return $v;
                        })
                    ->end()
                    ->arrayPrototype()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function (string $v) {
                                return ['template_directory' => $v];
                            })
                        ->end()
                        ->children()
                            ->scalarNode('template_directory')
                                ->defaultValue('components')
                            ->end()
                            ->scalarNode('name_prefix')
                                ->defaultValue('')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('anonymous_template_directory')
                    ->info('Defaults to `components`')
                ->end()
                ->scalarNode('component_css_generated_path_file')
                    ->info('Defaults to `./var/component-assets/component.css`')
                    ->defaultValue('%kernel.project_dir%/var/component-assets')
                ->end()
                ->scalarNode('component_css_path_file')
                    ->info('Defaults to `./assets/component.css`')
                    ->defaultValue('%kernel.project_dir%/assets')
                ->end()
                ->booleanNode('profiler')
                    ->info('Enables the profiler for Twig Component (in debug mode)')
                    ->defaultValue('%kernel.debug%')
                ->end()
                ->scalarNode('controllers_json')
                    ->setDeprecated('symfony/ux-twig-component', '2.18', 'The "twig_component.controllers_json" config option is deprecated, and will be removed in 3.0.')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return $this;
    }
}
