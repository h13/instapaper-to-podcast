<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Resilience;

/**
 * Metrics for circuit breaker
 */
final class CircuitBreakerMetrics
{
    private int $successCount = 0;
    private int $failureCount = 0;
    private int $consecutiveSuccesses = 0;
    private int $consecutiveFailures = 0;
    private float $totalDuration = 0.0;
    private bool $isOpen = false;
    private ?\DateTimeImmutable $openedAt = null;

    public function recordSuccess(float $duration): void
    {
        $this->successCount++;
        $this->consecutiveSuccesses++;
        $this->consecutiveFailures = 0;
        $this->totalDuration += $duration;
    }

    public function recordFailure(float $duration): void
    {
        $this->failureCount++;
        $this->consecutiveFailures++;
        $this->consecutiveSuccesses = 0;
        $this->totalDuration += $duration;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    public function getConsecutiveSuccesses(): int
    {
        return $this->consecutiveSuccesses;
    }

    public function getConsecutiveFailures(): int
    {
        return $this->consecutiveFailures;
    }

    public function getFailureRate(): float
    {
        $total = $this->successCount + $this->failureCount;
        if ($total === 0) {
            return 0.0;
        }

        return $this->failureCount / $total;
    }

    public function getAverageResponseTime(): float
    {
        $total = $this->successCount + $this->failureCount;
        if ($total === 0) {
            return 0.0;
        }

        return $this->totalDuration / $total;
    }

    public function markAsOpen(): void
    {
        $this->isOpen = true;
        $this->openedAt = new \DateTimeImmutable();
    }

    public function markAsClosed(): void
    {
        $this->isOpen = false;
        $this->openedAt = null;
        $this->consecutiveFailures = 0;
        $this->consecutiveSuccesses = 0;
    }

    public function shouldAttemptReset(int $resetTimeoutSeconds): bool
    {
        if (! $this->isOpen || $this->openedAt === null) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->openedAt->getTimestamp();

        return $diff >= $resetTimeoutSeconds;
    }

    /**
     * @return array{
     *   success_count: int,
     *   failure_count: int,
     *   failure_rate: float,
     *   average_response_time: float,
     *   is_open: bool,
     *   consecutive_failures: int,
     *   consecutive_successes: int
     * }
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function toArray(): array
    {
        return [
            'success_count' => $this->successCount,
            'failure_count' => $this->failureCount,
            'failure_rate' => $this->getFailureRate(),
            'average_response_time' => $this->getAverageResponseTime(),
            'is_open' => $this->isOpen,
            'consecutive_failures' => $this->consecutiveFailures,
            'consecutive_successes' => $this->consecutiveSuccesses,
        ];
    }
}
