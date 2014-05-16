<?php

namespace FM\CacheBundle\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

/**
 * Wrapper around Doctrine's entitymanager, which automatically caches queries
 * and individual entities. It also handles cache invalidation based on entity
 * events.
 *
 * There are two main ways to use this:
 *
 * 1. Use the get() method to find a cached instance of an entity, by id. This
 *    is meant as a super-fast, almost key-value store like, repository of
 *    entities.
 * 2. Use the query() method to query the database, but to use cached results
 *    where possible.
 *
 * The manager keeps track of cached queries and entities. Because it handles
 * entity lifecycle events, it knows when to invalidate a single entity or even
 * queries. For instance: when an entity is saved, it is purged from the cache.
 * When a new instance of that entity is added to the database, all cached
 * queries (and their result) that reference that entity are purged, so the
 * query may contain the new entity when executed again.
 *
 * @deprecated This bundle is superseded by https://packagist.org/packages/treehouselabs/cache-bundle
 */
class CachedEntityManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityCache
     */
    protected $cache;

    /**
     * @param EntityManager $em
     * @param EntityCache   $cache
     */
    public function __construct(EntityManager $em, EntityCache $cache)
    {
        trigger_error('This bundle is superseded by https://packagist.org/packages/treehouselabs/cache-bundle', E_USER_DEPRECATED);

        $this->em = $em;
        $this->cache = $cache;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function createQuery($dql)
    {
        return $this->em->createQuery($dql);
    }

    public function createQueryBuilder()
    {
        return $this->em->createQueryBuilder();
    }

    public function getQueryCacheKey(Query $q)
    {
        $hints = $q->getHints();
        ksort($hints);

        return md5(
            $q->getDql() . var_export($q->getParameters(), true) . var_export($hints, true) .
            '&firstResult=' . $q->getFirstResult() . '&maxResult=' . $q->getMaxResults() .
            '&hydrationMode=' . $q->getHydrationMode()
        );
    }

    public function has($key)
    {
        return $this->cache->has($key);
    }

    public function find($entity, $id, $ttl = null)
    {
        $q = $this->em->createQuery(sprintf('SELECT x FROM %s x WHERE x.id = :id', $entity));
        $q->setParameter('id', $id);

        if ($ttl !== false) {
            $key = sprintf($this->cache->getEntityKeyFormat(), $this->cache->getEntityClass($entity), $id);
            $q->useResultCache(true, $ttl, $key);
        }

        return $q->getOneOrNullResult();
    }

    public function query(Query $q, $ttl = null, $key = null)
    {
        if (is_null($key)) {
            $key = $this->getQueryCacheKey($q);
        }

        if ($ttl !== false) {
            $q->useResultCache(true, $ttl, $key);
        }

        $hasKey = $this->cache->has($key);

        // load via entitymanager
        $res = $q->getResult();

        // remember which queries contain these entities
        if (!empty($res) && ($hasKey === false) && ($ttl !== false)) {

            $this->cache->registerQueryForEntity($res[0], $key);

            foreach ($res as $entity) {
                $this->cache->registerQueryResult($entity, $key);
            }
        }

        return $res;
    }
}
