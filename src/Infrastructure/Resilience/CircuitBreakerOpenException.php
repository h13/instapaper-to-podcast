<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Resilience;

/**
 * Exception thrown when circuit breaker is open
 */
final class CircuitBreakerOpenException extends \RuntimeException
{
}
