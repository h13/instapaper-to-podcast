<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating configured loggers
 *
 * @psalm-suppress UnusedClass
 */
final class LoggerFactory
{
    private string $environment;
    private string $logPath;

    public function __construct(string $environment = 'production', ?string $logPath = null)
    {
        $this->environment = $environment;
        $this->logPath = $logPath ?? sys_get_temp_dir() . '/instapaper-to-podcast.log';
    }

    public function createLogger(string $channel): LoggerInterface
    {
        $logger = new Logger($channel);

        // Add processors for structured data
        $logger->pushProcessor(new UidProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());
        $logger->pushProcessor(new WebProcessor());
        $logger->pushProcessor(new ContextProcessor());

        // Configure handlers based on environment
        if ($this->environment === 'production') {
            $this->addProductionHandlers($logger);
        } else {
            $this->addDevelopmentHandlers($logger);
        }

        return $logger;
    }

    private function addProductionHandlers(Logger $logger): void
    {
        // JSON formatted logs for production
        $streamHandler = new StreamHandler($this->logPath, Level::Info);
        $streamHandler->setFormatter(new JsonFormatter());
        $logger->pushHandler($streamHandler);

        // Error logs to system error log
        $errorHandler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Level::Error);
        $logger->pushHandler($errorHandler);
    }

    private function addDevelopmentHandlers(Logger $logger): void
    {
        // Human-readable logs for development
        $streamHandler = new StreamHandler('php://stdout', Level::Debug);
        $logger->pushHandler($streamHandler);
    }
}
