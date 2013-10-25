<?php

namespace FM\CacheBundle\Tests\ORM;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group functional
 */
class EntityCacheTest extends WebTestCase
{
    private $cache;
    private $entity;
    private $entityKey;
    private $entityClass;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->cache = static::$kernel->getContainer()->get('fm_cache.entity_cache');
        // TODO should cache be cleared first?
        // $this->cache->clear();

        $this->entity = new EntityMock;
        $this->entityKey = $this->cache->getEntityKey($this->entity);
        $this->entityClass = $this->cache->getEntityClass($this->entity);
    }

    public function testEntityClass()
    {
        $class = 'fm-cachebundle-tests-orm-entitymock';

        $this->assertEquals($class, $this->cache->getEntityClass($this->entity));
        $this->assertEquals($class, $this->cache->getEntityClass(get_class($this->entity)));

        // register the non-existing namespace for testing purpose
        $em = static::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $em->getConfiguration()->addEntityNamespace('FMCacheBundle', 'FM\CacheBundle\Tests\Orm');

        $this->assertEquals($class, $this->cache->getEntityClass('FMCacheBundle:EntityMock'));
    }

    public function testCache()
    {
        $key = 'test.foo.bar';

        $this->cache->registerQueryResult($this->entity, $key);
        $this->assertContains($key, $this->cache->getRegisteredKeys($this->entityKey));

        $this->cache->registerQueryForEntity($this->entity, $key);
        $this->assertContains($key, $this->cache->getRegisteredKeys($this->entityClass));
    }

    public function testExpire()
    {
        $key = 'test.foo.bar';

        // cache a result and class for a fake query
        $this->cache->registerQueryResult($this->entity, $key);
        $this->cache->registerQueryForEntity($this->entity, $key);

        // expire the entity: entity key should be removed, class key not
        $this->cache->invalidateEntity($this->entity);
        $this->assertNotContains($key, $this->cache->getRegisteredKeys($this->entityKey));
        $this->assertContains($key, $this->cache->getRegisteredKeys($this->entityClass));

        // expire the entity: both should be removed
        $this->cache->invalidateEntityQueries($this->entity);
        $this->assertNotContains($key, $this->cache->getRegisteredKeys($this->entityClass));
    }
}
