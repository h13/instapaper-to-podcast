<?php

declare(strict_types=1);

namespace TextSummarizer\Module;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Ray\Di\ProviderInterface;

final class LoggerProvider implements ProviderInterface
{
    public function get(): LoggerInterface
    {
        $logger = new Logger('text-summarizer');

        // Add stream handler for stderr
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));

        // Add file handler if log path is set
        $logPath = $_ENV['LOG_PATH'] ?? null;
        if ($logPath) {
            $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
        }

        return $logger;
    }
}
