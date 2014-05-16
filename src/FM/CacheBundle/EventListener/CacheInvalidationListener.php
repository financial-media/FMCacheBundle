<?php

namespace FM\CacheBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use FM\CacheBundle\ORM\EntityCache;

/**
 * @deprecated This bundle is superseded by https://packagist.org/packages/treehouselabs/cache-bundle
 */
class CacheInvalidationListener
{
    /**
     * @var EntityCache
     */
    protected $entityCache;

    /**
     * @param EntityCache $entityCache
     */
    public function __construct(EntityCache $entityCache)
    {
        trigger_error('This bundle is superseded by https://packagist.org/packages/treehouselabs/cache-bundle', E_USER_DEPRECATED);

        $this->entityCache = $entityCache;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        $this->entityCache->invalidateEntity($entity);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        $this->entityCache->invalidateEntityQueries($entity);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        $this->entityCache->invalidateEntity($entity);
        $this->entityCache->invalidateEntityQueries($entity);
    }
}
