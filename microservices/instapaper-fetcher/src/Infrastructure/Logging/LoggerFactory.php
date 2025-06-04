<?php

declare(strict_types=1);

namespace InstapaperFetcher\Infrastructure\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public static function create(string $name): LoggerInterface
    {
        $logger = new Logger($name);
        $logFile = dirname(__DIR__, 3) . '/var/log/' . $name . '.log';
        
        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logger->pushHandler(new StreamHandler($logFile, Logger::INFO));
        
        return $logger;
    }
}