<?php

namespace GS\Command\Pass;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Mime\Email;
use GS\Service\Service\{
    ServiceContainer
};
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\{
    Parameter,
    Reference
};
use GS\Command\GSCommandExtension;

class MonologLoggerPass implements CompilerPassInterface
{
    public const GS_COMMAND_DEV_LOGGER_ID = 'monolog.handler.gs_command.dev_logger';

    public function __construct()
    {
    }

    public function process(ContainerBuilder $container)
    {
        $this->resetDevLoggerWhenAppEnvIsNotDev($container);
    }

    // ###> HELPER ###

    private function resetDevLoggerWhenAppEnvIsNotDev(
        ContainerBuilder $container,
    ): void {
        if (!$container->hasDefinition(self::GS_COMMAND_DEV_LOGGER_ID)) {
            return;
        }

        /*
            получить DYNAMIC env(<>) в проходе компилятора НЕВОЗМОЖНО!
        */
        $appEnv = $container->getParameter(
            ServiceContainer::getParameterName(
                GSCommandExtension::PREFIX,
                GSCommandExtension::APP_ENV,
            )
        );

        if ($appEnv == 'prod') {
            /* reset with null: 'monolog.handler.null_internal' */
            $container->setAlias(
                self::GS_COMMAND_DEV_LOGGER_ID,  # this service
                'monolog.handler.null_internal', # points to this service
            );
        }
    }

    //###< HELPER ###
}
