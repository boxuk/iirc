<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{


    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('box_uk_irc_logs_app');

        $this->addChannelBlacklist($rootNode);

        return $treeBuilder;
    }

    private function addChannelBlacklist(ArrayNodeDefinition $rootNode){
        $rootNode
            ->children()
                ->arrayNode('channels')
                    ->defaultTrue()
                    ->children()
                        ->arrayNode('blacklist')->defaultValue(array())->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();
    }
}
