<?php

namespace OwsProxy3\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Christian Wygoda
 */
class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fom_user');

        $rootNode
                ->children()
                ->scalarNode('logging')
                ->defaultTrue()
                ->end()
                ->scalarNode('obfuscate_client_ip')
                ->defaultTrue()
                ->end()
                ->arrayNode("proxy")
                ->canBeUnset()
                ->addDefaultsIfNotSet()
                ->children()
                ->scalarNode('host')->defaultNull()->end()
                ->scalarNode('port')->defaultNull()->end()
                ->scalarNode('timeout')->defaultNull()->end()
                ->scalarNode('user')->defaultNull()->end()
                ->scalarNode('password')->defaultNull()->end()
                ->arrayNode("noproxy")
                ->prototype('scalar')->end()
                ->end()
                ->end()
                ->end()
                ->end();

        return $treeBuilder;
    }

}