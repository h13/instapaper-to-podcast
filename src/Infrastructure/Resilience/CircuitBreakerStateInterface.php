<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Resilience;

/**
 * Interface for circuit breaker states
 */
interface CircuitBreakerStateInterface
{
    public function canAttemptCall(
        CircuitBreakerStorage $storage,
        CircuitBreakerConfig $config,
        string $serviceName
    ): bool;

    public function onSuccess(
        CircuitBreakerStorage $storage,
        CircuitBreakerConfig $config,
        string $serviceName
    ): CircuitBreakerStateInterface;

    public function onFailure(
        CircuitBreakerStorage $storage,
        CircuitBreakerConfig $config,
        string $serviceName
    ): CircuitBreakerStateInterface;

    public function getName(): string;
}
