<?php

namespace Symfony\UX\TwigComponent\Twig;

use Symfony\Bundle\TwigBundle\DependencyInjection\Configurator\EnvironmentConfigurator;
use Twig\Environment;

class TwigEnvironmentConfigurator
{
    public function __construct(
        private readonly EnvironmentConfigurator $decorated
    ) {
    }

    public function configure(Environment $environment): void
    {
        $this->decorated->configure($environment);

        $environment->setLexer(new ComponentLexer($environment));
    }
}
