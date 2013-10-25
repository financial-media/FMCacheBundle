<?php

namespace FM\CacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fm_cache');

        $rootNode
            ->children()
                ->scalarNode('cache_client')
                    ->isRequired()
                ->end()
            ->end();
        ;

        return $treeBuilder;
    }
}
