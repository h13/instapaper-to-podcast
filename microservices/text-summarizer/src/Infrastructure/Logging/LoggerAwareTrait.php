<?php

declare(strict_types=1);

namespace TextSummarizer\Infrastructure\Logging;

use Psr\Log\LoggerInterface;

trait LoggerAwareTrait
{
    protected LoggerInterface $logger;

    protected function logDebug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    protected function logCritical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }
}
