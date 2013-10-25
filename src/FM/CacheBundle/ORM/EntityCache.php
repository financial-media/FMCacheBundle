<?php

namespace FM\CacheBundle\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\Common\Cache\CacheProvider;
use FM\Cache\CacheInterface;

/**
 * Service that binds Doctrine's cache provider with our own meta class to
 * cache query results and helps with expiring entities. Used in conjunction
 * with the CachedEntityManager.
 */
class EntityCache
{
    /**
     * Cache to keep track of queries, results, etc. Used to automatically
     * register/invalidate entities.
     *
     * @var CacheInterface
     */
    protected $metaCache;

    /**
     * Doctrine's internal cache. This is the actual cache where the queries,
     * results and entities are stored/invalidated.
     *
     * @var CacheProvider
     */
    protected $entityCache;

    /**
     * @var Configuration
     */
    protected $ormConfig;

    /**
     * @param CacheInterface $cache
     * @param CacheProvider  $cacheProvider
     * @param Configuration  $ormConfig
     */
    public function __construct(CacheInterface $cache, CacheProvider $cacheProvider, Configuration $ormConfig)
    {
        $this->metaCache = $cache;
        $this->entityCache = $cacheProvider;
        $this->ormConfig = $ormConfig;
    }

    /**
     * Returns format for cache key
     *
     * @return string
     */
    public function getEntityKeyFormat()
    {
        return '%s:%s';
    }

    /**
     * Returns normalized cache key for entity class name
     *
     * @param  Object $entity
     * @return string
     */
    public function getEntityClass($entity)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);

            try {
                $proxyClass = new \ReflectionClass($entity);
                if ($proxyClass->implementsInterface('Doctrine\ORM\Proxy\Proxy')) {
                    $entity = $proxyClass->getParentClass()->getName();
                }
            } catch (\Exception $e) {}
        }

        // translate colon notation to FQCN
        if (false !== $pos = strrpos($entity, ':')) {
            list($namespaceAlias, $simpleClassName) = explode(':', $entity);
            $entity = $this->ormConfig->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;
        }

        return strtr(strtolower($entity), '\\', '-');
    }

    /**
     * Returns normalized cache key for entity instance
     *
     * @param  Object $entity
     * @return string
     */
    public function getEntityKey($entity)
    {
        return sprintf($this->getEntityKeyFormat(), $this->getEntityClass($entity), $entity->getId());
    }

    /**
     * The Doctrine cache driver is used for this, since the actual keys that are
     * used to store the results are changed by Doctrine (due to namespace support).
     *
     * @see https://doctrine-orm.readthedocs.org/en/latest/reference/caching.html#namespaces
     */
    public function has($key)
    {
        return $this->entityCache->contains($key);
    }

    /**
     * Registers a value to a list of known cache keys. Primarily used to assign
     * entities in a result set to the queries which resulted them. In this
     * case, the entity is the $key and the query is the $value.
     *
     * @param string $key   The list identifier
     * @param string $value The cache key to put in the list
     */
    public function register($key, $value)
    {
        $this->metaCache->appendToList(sprintf('registered:%s', $key), $value);
    }

    /**
     * Registers a query result to the meta cache.
     *
     * @param object $result        An entity from a query result
     * @param string $queryCacheKey The cache key for the query
     */
    public function registerQueryResult($result, $queryCacheKey)
    {
        $this->register($this->getEntityKey($result), $queryCacheKey);
    }

    /**
     * Registers an entity class from a query result to the meta cache.
     *
     * @param string $entityName    An entity class
     * @param string $queryCacheKey The cache key for the query
     */
    public function registerQueryForEntity($entityName, $queryCacheKey)
    {
        $this->register($this->getEntityClass($entityName), $queryCacheKey);
    }

    /**
     * Returns a list of cache keys registered to the given list key.
     *
     * @param  string $key
     * @return array
     */
    public function getRegisteredKeys($key)
    {
        return $this->metaCache->getListItems(sprintf('registered:%s', $key));
    }

    /**
     * Invalidates all cache keys registered to the given list key.
     *
     * @param string $key
     */
    public function invalidate($key)
    {
        // delete the item
        $this->entityCache->delete($key);

        // now delete all queries which results contain this item
        $list = sprintf('registered:%s', $key);
        $keys = $this->metaCache->getListItems($list);

        if (!empty($keys)) {
            foreach ($keys as $key) {
                $this->entityCache->delete($key);
            }
        }

        // finally remove the list which held the above queries
        $this->metaCache->remove($list);
    }

    /**
     * Invalidates a single entity by removing all cached queries registered to
     * this entity.
     *
     * @param object $entity
     */
    public function invalidateEntity($entity)
    {
        $this->invalidate($this->getEntityKey($entity));
    }

    /**
     * Invalidates a complete entity class by removing all cached queries
     * containing this entity.
     *
     * @param object $entity
     */
    public function invalidateEntityQueries($entity)
    {
        $this->invalidate($this->getEntityClass($entity));
    }

    /**
     * Clears both the meta cache and Doctrine's entity cache
     */
    public function clear()
    {
        $this->metaCache->clear();
        $this->entityCache->flushAll();
    }
}
