<?php

declare(strict_types=1);

namespace PodcastPublisher\Infrastructure\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public static function create(string $name): LoggerInterface
    {
        $logger = new Logger($name);
        
        $logLevel = $_ENV['APP_DEBUG'] ?? false ? Logger::DEBUG : Logger::INFO;
        $handler = new StreamHandler('php://stdout', $logLevel);
        
        $logger->pushHandler($handler);
        
        return $logger;
    }
}