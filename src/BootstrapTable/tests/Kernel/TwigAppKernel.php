<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\BootstrapTable\Tests\Kernel;


use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\BootstrapTable\BootstrapTableBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 *
 */
class TwigAppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new TwigBundle(), new WebpackEncoreBundle(), new BootstrapTableBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', ['secret' => '$ecret', 'test' => true]);
            $container->loadFromExtension('twig', ['default_path' => __DIR__.'/templates', 'strict_variables' => true, 'exception_controller' => null]);
            $container->loadFromExtension('webpack_encore', ['output_path' => '%kernel.project_dir%/public/build']);

            $container->setAlias('test.bootstrapTable.builder', 'bootstrapTable.builder')->setPublic(true);
            $container->setAlias('test.bootstrapTable.twig_extension', 'bootstrapTable.twig_extension')->setPublic(true);
        });
    }
}
