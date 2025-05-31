<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Trait to provide logger awareness
 */
trait LoggerAwareTrait
{
    private LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function getLogger(): LoggerInterface
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (! isset($this->logger)) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->getLogger()->debug($message, $this->enrichContext($context));
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->getLogger()->info($message, $this->enrichContext($context));
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->getLogger()->warning($message, $this->enrichContext($context));
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->getLogger()->error($message, $this->enrichContext($context));
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function enrichContext(array $context): array
    {
        $context['class'] = static::class;
        $context['trace_id'] = $this->getTraceId();

        return $context;
    }

    private function getTraceId(): string
    {
        /** @var ?string $traceId */
        static $traceId = null;
        if ($traceId === null) {
            $traceId = bin2hex(random_bytes(16));
        }

        return $traceId;
    }
}
