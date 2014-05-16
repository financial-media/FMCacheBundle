<?php

namespace FM\CacheBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @deprecated This bundle is superseded by https://packagist.org/packages/treehouselabs/cache-bundle
 */
class FMCacheExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        trigger_error('This bundle is superseded by https://packagist.org/packages/treehouselabs/cache-bundle', E_USER_DEPRECATED);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setAlias('fm_cache.cache', $config['cache_client']);
    }
}
