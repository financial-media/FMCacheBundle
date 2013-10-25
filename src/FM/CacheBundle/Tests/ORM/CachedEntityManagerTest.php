<?php

namespace FM\CacheBundle\Tests\ORM;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CachedEntityManagerTest extends WebTestCase
{
    private $manager;
    private $cache;

    private $entity;
    private $entityKey;
    private $entityClass;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->manager = static::$kernel->getContainer()->get('fm_cache.cached_entity_manager');
        $this->cache = $this->manager->getCache();
        $this->cache->clear();

        $this->markTestIncomplete('Mock EntityManager first');

        // $this->entity = $this->manager->createQuery('')->setMaxResults(1)->getSingleResult();
        // $this->entityKey = $this->cache->getEntityKey($this->entity);
        // $this->entityClass = $this->cache->getEntityClass($this->entity);
    }

    /**
     * @see http://stackoverflow.com/questions/10784973/how-to-set-up-database-heavy-unit-tests-in-symfony2-using-phpunit
     */
    public function testQueryCache()
    {
        $key = 'test.cm';

        // perform a query
        $q = $this->manager->createQuery('');
        $q->setParameter('id', $this->entity->getId());
        $this->manager->query($q, null, $key);

        // key should now be in cache
        $this->assertTrue($this->manager->has($key));

        // key should also be in cached list for the specific entity, and the entire entity class
        $this->assertContains($key, $this->cache->getRegisteredKeys($this->entityKey));
        $this->assertContains($key, $this->cache->getRegisteredKeys($this->entityClass));
    }

    public function testContainsQueryCache()
    {
        $key = 'test.cm';

        // perform a query
        $q = $this->manager->createQuery('');
        $q->setParameter('id', $this->entity->getId());
        $this->manager->query($q, null, $key);

        // reverse lookup: get the queries that have a result with this entity, and check if they're still cached
        foreach ($this->cache->getRegisteredKeys($this->entityKey) as $cacheKey) {
            $this->assertTrue($this->manager->has($cacheKey), sprintf('Cache must contain key "%s" after query', $cacheKey));
        }
    }

    public function testCacheExpiration()
    {
        $key = 'test.cm';

        // perform a query
        $q = $this->manager->createQuery('');
        $q->setParameter('id', $this->entity->getId());
        $res = $this->manager->query($q, null, $key);

        // get the cached keys
        $keys = $this->cache->getRegisteredKeys($this->entityKey);

        // expire this entity
        $this->cache->invalidateEntity($this->entity);

        // after expiration, cache no longer holds entity key
        $this->assertFalse($this->manager->has($this->entityKey));

        // queries with this entity in result have to be expired also
        foreach ($keys as $cacheKey) {
            $this->assertFalse($this->manager->has($cacheKey), sprintf('Cache should not have queries that contain expired entity ("%s" was found)', $cacheKey));
        }
    }

    public function testClassCacheExpiration()
    {
        $key = 'test.cm';

        // perform a query
        $q = $this->manager->createQuery('');
        $q->setParameter('id', $this->entity->getId());
        $res = $this->manager->query($q, null, $key);

        // remember the cached keys
        $keys = array();
        $classKeys = $this->cache->getRegisteredKeys($this->entityClass);

        // cache some individual items
        foreach ($res as $item) {
            $itemKeys = $this->cache->getRegisteredKeys($this->cache->getEntityKey($item));
            $keys[$item->getId()] = $itemKeys;

            $this->assertContains($key, $itemKeys);
        }

        // expire the entire entity class
        $this->cache->invalidateEntityQueries($this->entityClass);

        // the query we performed should be removed from cache
        $this->assertFalse($this->manager->has($key), sprintf('Cache should not have query that contains entity of expired class ("%s" was found)', $key));

        // individual items should be removed from cache
        foreach ($keys as $id => $itemKeys) {
            foreach ($itemKeys as $key) {
                $this->assertFalse($this->manager->has($key), sprintf('Cache should not have query that contains entity of expired class ("%s" was found)', $key));
            }
        }

        // all queries that have these type of entities should be removed
        foreach ($classKeys as $key) {
            $this->assertFalse($this->manager->has($key));
        }
    }
}
