<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cache;

use InstapaperToPodcast\Infrastructure\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;

final class CacheManagerTest extends TestCase
{
    private CacheItemPoolInterface $pool;
    private CacheManager $cache;

    protected function setUp(): void
    {
        $this->pool = $this->createMock(CacheItemPoolInterface::class);
        $this->cache = new CacheManager($this->pool, 'test', new NullLogger());
    }

    public function testRememberReturnsCachedValue(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn('cached value');

        $this->pool->method('getItem')->with('test:key')->willReturn($item);

        $callbackCalled = false;
        $result = $this->cache->remember('key', 3600, function () use (&$callbackCalled) {
            $callbackCalled = true;

            return 'new value';
        });

        $this->assertEquals('cached value', $result);
        $this->assertFalse($callbackCalled);

        $metrics = $this->cache->getMetrics();
        $this->assertEquals(1, $metrics['hits']);
        $this->assertEquals(0, $metrics['misses']);
    }

    public function testRememberComputesAndCachesOnMiss(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);
        $item->expects($this->once())->method('set')->with('computed value');
        $item->expects($this->once())->method('expiresAfter')->with(3600);

        $this->pool->method('getItem')->with('test:key')->willReturn($item);
        $this->pool->expects($this->once())->method('save')->with($item)->willReturn(true);

        $result = $this->cache->remember('key', 3600, fn () => 'computed value');

        $this->assertEquals('computed value', $result);

        $metrics = $this->cache->getMetrics();
        $this->assertEquals(0, $metrics['hits']);
        $this->assertEquals(1, $metrics['misses']);
    }

    public function testSet(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())->method('set')->with('value');
        $item->expects($this->once())->method('expiresAfter')->with(3600);

        $this->pool->method('getItem')->with('test:key')->willReturn($item);
        $this->pool->expects($this->once())->method('save')->with($item)->willReturn(true);

        $result = $this->cache->set('key', 'value', 3600);

        $this->assertTrue($result);
    }

    public function testGet(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn('cached value');

        $this->pool->method('getItem')->with('test:key')->willReturn($item);

        $result = $this->cache->get('key');

        $this->assertEquals('cached value', $result);
    }

    public function testGetReturnsNullOnMiss(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);

        $this->pool->method('getItem')->with('test:key')->willReturn($item);

        $result = $this->cache->get('key');

        $this->assertNull($result);
    }

    public function testDelete(): void
    {
        $this->pool->expects($this->once())
            ->method('deleteItem')
            ->with('test:key')
            ->willReturn(true);

        $result = $this->cache->delete('key');

        $this->assertTrue($result);
    }

    public function testDeleteMultiple(): void
    {
        $this->pool->expects($this->once())
            ->method('deleteItems')
            ->with(['test:key1', 'test:key2', 'test:key3'])
            ->willReturn(true);

        $result = $this->cache->deleteMultiple(['key1', 'key2', 'key3']);

        $this->assertTrue($result);
    }

    public function testClear(): void
    {
        $this->pool->expects($this->once())
            ->method('clear')
            ->willReturn(true);

        $result = $this->cache->clear();

        $this->assertTrue($result);

        $metrics = $this->cache->getMetrics();
        $this->assertEquals(0, $metrics['hits']);
        $this->assertEquals(0, $metrics['misses']);
        $this->assertEquals(0.0, $metrics['hit_rate']);
    }

    public function testNamespacing(): void
    {
        $cache = new CacheManager($this->pool, '', new NullLogger());

        $item = $this->createMock(CacheItemInterface::class);
        $this->pool->method('getItem')->with('key')->willReturn($item);

        $cache->get('key');

        // Without namespace, key should be used as-is
        $this->assertTrue(true);
    }
}
