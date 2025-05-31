<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Resilience;

/**
 * Open state - circuit is tripped, requests are blocked
 */
final class OpenState implements CircuitBreakerStateInterface
{
    public function canAttemptCall(
        CircuitBreakerStorage $storage,
        CircuitBreakerConfig $config,
        string $serviceName
    ): bool {
        $metrics = $storage->getMetrics($serviceName);

        if ($metrics->shouldAttemptReset($config->getResetTimeout())) {
            return true;
        }

        return false;
    }

    public function onSuccess(
        CircuitBreakerStorage $storage,
        CircuitBreakerConfig $config,
        string $serviceName
    ): CircuitBreakerStateInterface {
        return new HalfOpenState();
    }

    public function onFailure(
        CircuitBreakerStorage $storage,
        CircuitBreakerConfig $config,
        string $serviceName
    ): CircuitBreakerStateInterface {
        return $this;
    }

    public function getName(): string
    {
        return 'open';
    }
}
