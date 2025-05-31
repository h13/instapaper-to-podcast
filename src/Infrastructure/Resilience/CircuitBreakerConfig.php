<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Resilience;

/**
 * Configuration for circuit breaker
 */
final class CircuitBreakerConfig
{
    private int $failureThreshold;
    private int $successThreshold;
    private int $timeout;
    private int $resetTimeout;
    /** @var class-string<\Throwable>[] */
    private array $ignoredExceptions;

    /**
     * @param class-string<\Throwable>[] $ignoredExceptions
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        int $failureThreshold = 5,
        int $successThreshold = 2,
        int $timeout = 60,
        int $resetTimeout = 30,
        array $ignoredExceptions = []
    ) {
        $this->failureThreshold = $failureThreshold;
        $this->successThreshold = $successThreshold;
        $this->timeout = $timeout;
        $this->resetTimeout = $resetTimeout;
        $this->ignoredExceptions = $ignoredExceptions;
    }

    public static function default(): self
    {
        return new self();
    }

    public function getFailureThreshold(): int
    {
        return $this->failureThreshold;
    }

    public function getSuccessThreshold(): int
    {
        return $this->successThreshold;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getResetTimeout(): int
    {
        return $this->resetTimeout;
    }

    public function shouldFallbackOnException(\Throwable $exception): bool
    {
        foreach ($this->ignoredExceptions as $ignoredException) {
            if ($exception instanceof $ignoredException) {
                return false;
            }
        }

        return true;
    }
}
