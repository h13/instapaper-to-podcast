<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Resilience;

/**
 * Half-open state - testing if service has recovered
 */
final class HalfOpenState implements CircuitBreakerStateInterface
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
        $metrics = $storage->getMetrics($serviceName);

        if ($metrics->getConsecutiveSuccesses() >= $config->getSuccessThreshold()) {
            $storage->markAsClosed($serviceName);

            return new ClosedState();
        }

        return $this;
    }

    public function onFailure(
        CircuitBreakerStorage $storage,
        CircuitBreakerConfig $config,
        string $serviceName
    ): CircuitBreakerStateInterface {
        $storage->markAsOpen($serviceName);

        return new OpenState();
    }

    public function getName(): string
    {
        return 'half-open';
    }
}
