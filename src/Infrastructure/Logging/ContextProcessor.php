<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Infrastructure\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds application context to log records
 */
final class ContextProcessor implements ProcessorInterface
{
    private string $version;
    private string $environment;

    public function __construct(string $version = '1.0.0', string $environment = 'production')
    {
        $this->version = $version;
        $this->environment = $environment;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['app_version'] = $this->version;
        $record->extra['environment'] = $this->environment;
        $record->extra['php_version'] = PHP_VERSION;
        $record->extra['timestamp_ms'] = (int) (microtime(true) * 1000);

        return $record;
    }
}
