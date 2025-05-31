<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Resilience;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Retry policy with exponential backoff
 */
final class RetryPolicy
{
    private int $maxAttempts;
    private int $baseDelayMs;
    private float $multiplier;
    private int $maxDelayMs;
    private LoggerInterface $logger;
    /** @var class-string<\Throwable>[] */
    private array $retryableExceptions;

    /**
     * @param class-string<\Throwable>[] $retryableExceptions
     */
    public function __construct(
        int $maxAttempts = 3,
        int $baseDelayMs = 100,
        float $multiplier = 2.0,
        int $maxDelayMs = 5000,
        array $retryableExceptions = [],
        ?LoggerInterface $logger = null
    ) {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('Max attempts must be at least 1');
        }

        $this->maxAttempts = $maxAttempts;
        $this->baseDelayMs = $baseDelayMs;
        $this->multiplier = $multiplier;
        $this->maxDelayMs = $maxDelayMs;
        $this->retryableExceptions = $retryableExceptions;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws \Throwable
     */
    public function execute(callable $operation)
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                $this->logger->debug('Executing operation', [
                    'attempt' => $attempt,
                    'max_attempts' => $this->maxAttempts,
                ]);

                return $operation();
            } catch (\Throwable $e) {
                $lastException = $e;

                if (! $this->shouldRetry($e, $attempt)) {
                    throw $e;
                }

                $delay = $this->calculateDelay($attempt);

                $this->logger->warning('Operation failed, retrying', [
                    'attempt' => $attempt,
                    'max_attempts' => $this->maxAttempts,
                    'delay_ms' => $delay,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                usleep(max(0, $delay * 1000));
            }
        }

        throw $lastException ?? new \RuntimeException('Retry failed with no exception');
    }

    private function shouldRetry(\Throwable $exception, int $attempt): bool
    {
        if ($attempt >= $this->maxAttempts) {
            return false;
        }

        if ($this->retryableExceptions === []) {
            return true;
        }

        foreach ($this->retryableExceptions as $retryableException) {
            if ($exception instanceof $retryableException) {
                return true;
            }
        }

        return false;
    }

    private function calculateDelay(int $attempt): int
    {
        $delay = (int) ($this->baseDelayMs * pow($this->multiplier, $attempt - 1));

        // Add jitter (±10%)
        $jitter = (int) ($delay * 0.1);
        $delay = $delay + random_int(-$jitter, $jitter);

        return min($delay, $this->maxDelayMs);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function immediate(): self
    {
        return new self(1, 0);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function linear(int $maxAttempts = 3, int $delayMs = 1000): self
    {
        return new self($maxAttempts, $delayMs, 1.0);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function exponential(int $maxAttempts = 3, int $baseDelayMs = 100): self
    {
        return new self($maxAttempts, $baseDelayMs, 2.0);
    }
}
