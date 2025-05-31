<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Resilience;

/**
 * Circuit breaker implementation for fault tolerance
 */
final class CircuitBreaker
{
    private CircuitBreakerStateInterface $state;
    private CircuitBreakerStorage $storage;
    private CircuitBreakerConfig $config;
    private string $serviceName;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        string $serviceName,
        ?CircuitBreakerConfig $config = null,
        ?CircuitBreakerStorage $storage = null
    ) {
        $this->serviceName = $serviceName;
        $this->config = $config ?? CircuitBreakerConfig::default();
        $this->storage = $storage ?? new CircuitBreakerStorage();
        $this->state = new ClosedState();
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws CircuitBreakerOpenException
     * @throws \Throwable
     */
    public function call(callable $operation)
    {
        if (! $this->canAttemptCall()) {
            throw new CircuitBreakerOpenException(
                "Circuit breaker is open for service: {$this->serviceName}"
            );
        }

        $startTime = microtime(true);

        try {
            $result = $operation();
            $this->recordSuccess(microtime(true) - $startTime);

            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure(microtime(true) - $startTime);

            throw $e;
        }
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @param callable(): T $fallback
     * @return T
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function callWithFallback(callable $operation, callable $fallback)
    {
        try {
            return $this->call($operation);
        } catch (CircuitBreakerOpenException $e) {
            return $fallback();
        } catch (\Throwable $e) {
            if ($this->config->shouldFallbackOnException($e)) {
                return $fallback();
            }

            throw $e;
        }
    }

    private function canAttemptCall(): bool
    {
        return $this->state->canAttemptCall($this->storage, $this->config, $this->serviceName);
    }

    private function recordSuccess(float $duration): void
    {
        $this->storage->recordSuccess($this->serviceName, $duration);
        $this->state = $this->state->onSuccess($this->storage, $this->config, $this->serviceName);
    }

    private function recordFailure(float $duration): void
    {
        $this->storage->recordFailure($this->serviceName, $duration);
        $this->state = $this->state->onFailure($this->storage, $this->config, $this->serviceName);
    }


    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getState(): string
    {
        return $this->state->getName();
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function reset(): void
    {
        $this->storage->reset($this->serviceName);
        $this->state = new ClosedState();
    }
}
