<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Module;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TextSummarizer\Module\LoggerProvider;

/**
 * @covers \TextSummarizer\Module\LoggerProvider
 */
final class LoggerProviderTest extends TestCase
{
    public function testGetReturnsLogger(): void
    {
        $provider = new LoggerProvider();
        $logger = $provider->get();
        
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertEquals('text-summarizer', $logger->getName());
    }

    public function testGetWithLogPath(): void
    {
        $logPath = sys_get_temp_dir() . '/test-' . uniqid() . '.log';
        $_ENV['LOG_PATH'] = $logPath;
        
        $provider = new LoggerProvider();
        $logger = $provider->get();
        
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(Logger::class, $logger);
        
        // Check that two handlers are added (stderr + file)
        $handlers = $logger->getHandlers();
        $this->assertCount(2, $handlers);
        
        unset($_ENV['LOG_PATH']);
        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }
}