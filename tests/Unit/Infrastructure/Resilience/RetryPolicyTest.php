<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Resilience;

use InstapaperToPodcast\Infrastructure\Resilience\RetryPolicy;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class RetryPolicyTest extends TestCase
{
    public function testSuccessfulOperationReturnsImmediately(): void
    {
        $policy = new RetryPolicy(maxAttempts: 3);
        $attempts = 0;

        $result = $policy->execute(function () use (&$attempts) {
            $attempts++;

            return 'success';
        });

        $this->assertEquals('success', $result);
        $this->assertEquals(1, $attempts);
    }

    public function testRetriesOnFailure(): void
    {
        $policy = new RetryPolicy(
            maxAttempts: 3,
            baseDelayMs: 10,
            logger: new NullLogger()
        );

        $attempts = 0;

        $result = $policy->execute(function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new \RuntimeException('Temporary failure');
            }

            return 'success';
        });

        $this->assertEquals('success', $result);
        $this->assertEquals(3, $attempts);
    }

    public function testFailsAfterMaxAttempts(): void
    {
        $policy = new RetryPolicy(maxAttempts: 2, baseDelayMs: 10);
        $attempts = 0;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Always fails');

        $policy->execute(function () use (&$attempts) {
            $attempts++;

            throw new \RuntimeException('Always fails');
        });

        $this->assertEquals(2, $attempts);
    }

    public function testOnlyRetriesSpecificExceptions(): void
    {
        $policy = new RetryPolicy(
            maxAttempts: 3,
            baseDelayMs: 10,
            retryableExceptions: [\RuntimeException::class]
        );

        $this->expectException(\InvalidArgumentException::class);

        $attempts = 0;
        $policy->execute(function () use (&$attempts) {
            $attempts++;

            throw new \InvalidArgumentException('Not retryable');
        });

        $this->assertEquals(1, $attempts);
    }

    public function testExponentialBackoff(): void
    {
        $policy = RetryPolicy::exponential(maxAttempts: 3, baseDelayMs: 100);

        // Test the delay calculation logic
        $reflection = new \ReflectionClass($policy);
        $method = $reflection->getMethod('calculateDelay');
        $method->setAccessible(true);

        // First retry: ~100ms
        $delay1 = $method->invoke($policy, 1);
        $this->assertGreaterThanOrEqual(90, $delay1);
        $this->assertLessThanOrEqual(110, $delay1);

        // Second retry: ~200ms
        $delay2 = $method->invoke($policy, 2);
        $this->assertGreaterThanOrEqual(180, $delay2);
        $this->assertLessThanOrEqual(220, $delay2);

        // Third retry: ~400ms
        $delay3 = $method->invoke($policy, 3);
        $this->assertGreaterThanOrEqual(360, $delay3);
        $this->assertLessThanOrEqual(440, $delay3);
    }

    public function testLinearBackoff(): void
    {
        $policy = RetryPolicy::linear(maxAttempts: 3, delayMs: 100);

        $reflection = new \ReflectionClass($policy);
        $method = $reflection->getMethod('calculateDelay');
        $method->setAccessible(true);

        // All retries should have ~100ms delay
        for ($i = 1; $i <= 3; $i++) {
            $delay = $method->invoke($policy, $i);
            $this->assertGreaterThanOrEqual(90, $delay);
            $this->assertLessThanOrEqual(110, $delay);
        }
    }

    public function testImmediateRetry(): void
    {
        $policy = RetryPolicy::immediate();

        $this->expectException(\RuntimeException::class);

        $attempts = 0;
        $policy->execute(function () use (&$attempts) {
            $attempts++;

            throw new \RuntimeException('Fails');
        });

        $this->assertEquals(1, $attempts);
    }
}
