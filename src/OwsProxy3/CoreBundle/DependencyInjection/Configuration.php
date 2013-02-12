<?php

/**
 * @author Christian Wygoda
 */

namespace OwsProxy3\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder() {
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
            ->end();

        return $treeBuilder;
    }
}
