<?php

namespace FM\CacheBundle\Tests\Cache;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group functional
 */
class CacheTest extends WebTestCase
{
    private $cache;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->cache = static::$kernel->getContainer()->get('fm_cache.cache');
    }

    public function testGetSet()
    {
        $this->cache->set('foo', 'bar');
        $this->assertEquals('bar', $this->cache->get('foo'));
    }

    public function testRemove()
    {
        $this->cache->set('foo', 'bar');
        $this->cache->remove('foo');

        $this->assertFalse($this->cache->has('foo'));
        $this->assertNull($this->cache->get('foo'));
    }

    public function testList()
    {
        $list = 'testlist';

        // add items to list
        $this->cache->appendToList($list, 'foo');
        $this->cache->appendToList($list, 'bar');

        $items = $this->cache->getListItems($list);

        // items should be in list
        $this->assertContains('foo', $items);
        $this->assertContains('bar', $items);

        // remove item from list
        $this->cache->removeFromList($list, 'foo');

        $items = $this->cache->getListItems($list);

        // foo should be removed, but bar not
        $this->assertNotContains('foo', $items);
        $this->assertContains('bar', $items);
    }

    public function testClearCache()
    {
        $this->cache->set('foo', 'bar');
        $this->cache->set('foobar', 'baz');

        $this->cache->clear();

        $this->assertFalse($this->cache->has('foo'));
        $this->assertFalse($this->cache->has('foobar'));
    }

    public function testLocalCache()
    {
        $this->cache->clear();

        $this->cache->set('foo', 'bar');
        $this->assertArrayHasKey('foo', \PHPUnit_Framework_Assert::readAttribute($this->cache, 'localCache'));

        $this->cache->remove('foo');
        $this->assertArrayNotHasKey('foo', \PHPUnit_Framework_Assert::readAttribute($this->cache, 'localCache'));
    }
}
