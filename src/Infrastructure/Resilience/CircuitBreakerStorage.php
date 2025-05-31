<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Resilience;

/**
 * Storage for circuit breaker metrics
 */
final class CircuitBreakerStorage
{
    /** @var array<string, CircuitBreakerMetrics> */
    private array $metrics = [];

    public function recordSuccess(string $serviceName, float $duration): void
    {
        $metrics = $this->getMetrics($serviceName);
        $metrics->recordSuccess($duration);
    }

    public function recordFailure(string $serviceName, float $duration): void
    {
        $metrics = $this->getMetrics($serviceName);
        $metrics->recordFailure($duration);
    }

    public function getMetrics(string $serviceName): CircuitBreakerMetrics
    {
        if (! isset($this->metrics[$serviceName])) {
            $this->metrics[$serviceName] = new CircuitBreakerMetrics();
        }

        return $this->metrics[$serviceName];
    }

    public function markAsOpen(string $serviceName): void
    {
        $metrics = $this->getMetrics($serviceName);
        $metrics->markAsOpen();
    }

    public function markAsClosed(string $serviceName): void
    {
        $metrics = $this->getMetrics($serviceName);
        $metrics->markAsClosed();
    }

    public function reset(string $serviceName): void
    {
        unset($this->metrics[$serviceName]);
    }
}
