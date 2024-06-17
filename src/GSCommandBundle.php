<?php

namespace GS\Command;

use GS\Service\Service\{
    ServiceContainer,
    StringNormalizer
};
use Symfony\Component\DependencyInjection\{
    Parameter,
    Reference
};
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveEnvPlaceholdersPass;
use GS\Command\GSCommandExtension;
use GS\Command\Pass\MonologLoggerPass;

class GSCommandBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container
            /*
            ->addCompilerPass(
                new MonologLoggerPass(),
                //type: PassConfig::TYPE_AFTER_REMOVING,
            )
            */
        ;
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->extension === null) {
            $this->extension = new GSCommandExtension();
        }

        return $this->extension;
    }
}
