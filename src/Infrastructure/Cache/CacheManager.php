<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Cache manager with advanced features
 */
final class CacheManager
{
    private CacheItemPoolInterface $pool;
    private LoggerInterface $logger;
    private CacheMetrics $metrics;
    private string $namespace;

    public function __construct(
        CacheItemPoolInterface $pool,
        string $namespace = '',
        ?LoggerInterface $logger = null
    ) {
        $this->pool = $pool;
        $this->namespace = $namespace;
        $this->logger = $logger ?? new NullLogger();
        $this->metrics = new CacheMetrics();
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $cacheKey = $this->buildKey($key);
        $item = $this->pool->getItem($cacheKey);

        if ($item->isHit()) {
            $this->metrics->recordHit();
            $this->logger->debug('Cache hit', ['key' => $key]);

            /** @var T */
            return $item->get();
        }

        $this->metrics->recordMiss();
        $this->logger->debug('Cache miss', ['key' => $key]);

        $value = $callback();

        $item->set($value);
        $item->expiresAfter($ttl);

        if ($this->pool->save($item)) {
            $this->logger->debug('Cache set', ['key' => $key, 'ttl' => $ttl]);
        } else {
            $this->logger->warning('Failed to save cache', ['key' => $key]);
        }

        return $value;
    }

    /**
     * @return array{hits: int, misses: int, hit_rate: float}
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getMetrics(): array
    {
        return $this->metrics->toArray();
    }

    /**
     * Set a value in the cache
     *
     * @param mixed $value
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $cacheKey = $this->buildKey($key);
        $item = $this->pool->getItem($cacheKey);

        $item->set($value);
        $item->expiresAfter($ttl);

        $result = $this->pool->save($item);

        if ($result) {
            $this->logger->debug('Cache set', ['key' => $key, 'ttl' => $ttl]);
        } else {
            $this->logger->warning('Failed to save cache', ['key' => $key]);
        }

        return $result;
    }

    /**
     * Get a value from the cache
     *
     * @param string|null $type
     * @return mixed|null
     * @psalm-suppress PossiblyUnusedMethod
     * @psalm-suppress UnusedParam
     */
    public function get(string $key, ?string $type = null)
    {
        $cacheKey = $this->buildKey($key);
        $item = $this->pool->getItem($cacheKey);

        if ($item->isHit()) {
            $this->metrics->recordHit();
            $this->logger->debug('Cache hit', ['key' => $key]);

            return $item->get();
        }

        $this->metrics->recordMiss();
        $this->logger->debug('Cache miss', ['key' => $key]);

        return null;
    }

    /**
     * Delete a key from the cache
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function delete(string $key): bool
    {
        $cacheKey = $this->buildKey($key);
        $result = $this->pool->deleteItem($cacheKey);

        if ($result) {
            $this->logger->debug('Cache deleted', ['key' => $key]);
        } else {
            $this->logger->warning('Failed to delete cache', ['key' => $key]);
        }

        return $result;
    }

    /**
     * Delete multiple keys from the cache
     *
     * @param string[] $keys
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function deleteMultiple(array $keys): bool
    {
        $cacheKeys = array_map([$this, 'buildKey'], $keys);
        $result = $this->pool->deleteItems($cacheKeys);

        if ($result) {
            $this->logger->debug('Cache batch deleted', ['keys' => $keys]);
        } else {
            $this->logger->warning('Failed to delete cache batch', ['keys' => $keys]);
        }

        return $result;
    }

    /**
     * Clear all cached items
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function clear(): bool
    {
        $result = $this->pool->clear();

        if ($result) {
            $this->metrics->reset();
            $this->logger->info('Cache cleared');
        } else {
            $this->logger->error('Failed to clear cache');
        }

        return $result;
    }

    private function buildKey(string $key): string
    {
        if ($this->namespace === '') {
            return $key;
        }

        return $this->namespace . ':' . $key;
    }
}
