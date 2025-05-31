<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Cache;

/**
 * Cache metrics tracker
 */
final class CacheMetrics
{
    private int $hits = 0;
    private int $misses = 0;

    public function recordHit(): void
    {
        $this->hits++;
    }

    public function recordMiss(): void
    {
        $this->misses++;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getHits(): int
    {
        return $this->hits;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getMisses(): int
    {
        return $this->misses;
    }

    public function getHitRate(): float
    {
        $total = $this->hits + $this->misses;
        if ($total === 0) {
            return 0.0;
        }

        return $this->hits / $total;
    }

    /**
     * @return array{hits: int, misses: int, hit_rate: float}
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function toArray(): array
    {
        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'hit_rate' => $this->getHitRate(),
        ];
    }

    /**
     * Reset all metrics
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function reset(): void
    {
        $this->hits = 0;
        $this->misses = 0;
    }
}
