<?php

namespace Symfony\UX\TwigComponent\Twig;

use Symfony\Bundle\TwigBundle\DependencyInjection\Configurator\EnvironmentConfigurator;
use Twig\Environment;

class TwigEnvironmentConfigurator
{
    private EnvironmentConfigurator $decorated;

    public function __construct(
        EnvironmentConfigurator $decorated
    ) {
        $this->decorated = $decorated;
    }

    public function configure(Environment $environment): void
    {
        $this->decorated->configure($environment);

        $environment->setLexer(new ComponentLexer($environment));
    }
}
