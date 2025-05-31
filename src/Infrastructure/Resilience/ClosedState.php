<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Resilience;

/**
 * Closed state - normal operation
 */
final class ClosedState implements CircuitBreakerStateInterface
{
    public function canAttemptCall(
        CircuitBreakerStorage $storage,
        CircuitBreakerConfig $config,
        string $serviceName
    ): bool {
        return true;
    }

    public function onSuccess(
        CircuitBreakerStorage $storage,
        CircuitBreakerConfig $config,
        string $serviceName
    ): CircuitBreakerStateInterface {
        return $this;
    }

    public function onFailure(
        CircuitBreakerStorage $storage,
        CircuitBreakerConfig $config,
        string $serviceName
    ): CircuitBreakerStateInterface {
        $metrics = $storage->getMetrics($serviceName);

        if ($metrics->getConsecutiveFailures() >= $config->getFailureThreshold()) {
            $storage->markAsOpen($serviceName);

            return new OpenState();
        }

        return $this;
    }

    public function getName(): string
    {
        return 'closed';
    }
}
