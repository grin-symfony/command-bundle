<?php

namespace GS\Command;

use function Symfony\Component\String\u;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use GS\Command\GSCommandExtension;

class Configuration implements ConfigurationInterface
{
    public function __construct(
        private readonly array $progressBarSpin,
    ) {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(GSCommandExtension::PREFIX);

        $treeBuilder->getRootNode()
            ->info(''
                . 'You can copy this example: "'
                . \dirname(__DIR__)
                . DIRECTORY_SEPARATOR . 'config'
                . DIRECTORY_SEPARATOR . 'packages'
                . DIRECTORY_SEPARATOR . 'gs_command.yaml'
                . '"')
            ->children()

                ->scalarNode(GSCommandExtension::APP_ENV)
                    ->info('env(APP_ENV) of the project')
                    ->isRequired()
                    #->defaultValue('%gs_command.locale%') Don't work, it's a simple string
                ->end()

                ->booleanNode(GSCommandExtension::DISPLAY_INIT_HELP)
                    ->info('Display to user init help information of this bundle')
                    ->defaultValue('%env(bool:GS_COMMAND_DISPLAY_INIT_HELP_MESSAGE)%')
                ->end()

                ->arrayNode(GSCommandExtension::PROGRESS_BAR_SPIN)
                ->info('Array with the animation elements')
                ->beforeNormalization()
                    // adds space in the end of each el
                    ->always(static function ($array): array {
                        return \array_map(static fn($v) => (string) u($v)->ensureEnd(' '), $array);
                    })
                ->end()
                    ->defaultValue($this->progressBarSpin)
                    ->scalarPrototype()->end()
                ->end()

            ->end()
        ;

        //$treeBuilder->setPathSeparator('/');

        return $treeBuilder;
    }

    //###> HELPERS ###
}
