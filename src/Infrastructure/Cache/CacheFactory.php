<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Factory for creating cache instances
 *
 * @psalm-suppress UnusedClass
 */
final class CacheFactory
{
    public static function create(
        string $type = 'filesystem',
        string $namespace = 'instapaper_podcast',
        ?LoggerInterface $logger = null
    ): CacheManager {
        $pool = match ($type) {
            'memory' => self::createMemoryCache($namespace),
            'apcu' => self::createApcuCache($namespace),
            'redis' => self::createRedisCache($namespace),
            'filesystem' => self::createFilesystemCache($namespace),
            default => throw new \InvalidArgumentException("Unknown cache type: {$type}"),
        };

        return new CacheManager($pool, $namespace, $logger);
    }

    /**
     * @psalm-suppress UnusedParam
     */
    private static function createMemoryCache(string $namespace): CacheItemPoolInterface
    {
        return new ArrayAdapter(
            defaultLifetime: 3600,
            storeSerialized: true,
            maxLifetime: 0,
            maxItems: 1000
        );
    }

    private static function createApcuCache(string $namespace): CacheItemPoolInterface
    {
        if (! extension_loaded('apcu') || ! apcu_enabled()) {
            throw new \RuntimeException('APCu is not available');
        }

        return new ApcuAdapter(
            namespace: $namespace,
            defaultLifetime: 3600
        );
    }

    private static function createRedisCache(string $namespace): CacheItemPoolInterface
    {
        if (! extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension is not available');
        }

        $redis = new \Redis();
        $redisHost = $_ENV['REDIS_HOST'] ?? 'localhost';

        $redisPort = 6379;
        if (isset($_ENV['REDIS_PORT'])) {
            $port = $_ENV['REDIS_PORT'];
            if (is_numeric($port)) {
                $redisPort = (int) $port;
            }
        }

        /** @phpstan-ignore-next-line */
        if (! $redis->connect($redisHost, $redisPort)) {
            throw new \RuntimeException('Failed to connect to Redis');
        }

        return new RedisAdapter(
            redis: $redis,
            namespace: $namespace,
            defaultLifetime: 3600
        );
    }

    private static function createFilesystemCache(string $namespace): CacheItemPoolInterface
    {
        $cacheDir = $_ENV['CACHE_DIR'] ?? sys_get_temp_dir() . '/instapaper-podcast-cache';

        return new FilesystemAdapter(
            namespace: $namespace,
            defaultLifetime: 3600,
            /** @phpstan-ignore-next-line */
            directory: $cacheDir
        );
    }
}
