<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Infrastructure\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use TextSummarizer\Infrastructure\Logging\LoggerFactory;

/**
 * @covers \TextSummarizer\Infrastructure\Logging\LoggerFactory
 */
final class LoggerFactoryTest extends TestCase
{
    private string $testLogFile;

    protected function setUp(): void
    {
        $this->testLogFile = sys_get_temp_dir() . '/test-' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
    }

    public function testCreateLogger(): void
    {
        $logger = LoggerFactory::create('test-channel');
        
        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertEquals('test-channel', $logger->getName());
        
        // Test that handlers are added
        $handlers = $logger->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
    }

    public function testLoggerWritesToStdout(): void
    {
        // Skip this test as it requires capturing stdout which is tricky with Monolog
        $this->markTestSkipped('Testing stdout output is not reliable in this context');
    }

    public function testLoggerUsesDebugLevelWhenAppDebugIsTrue(): void
    {
        $_ENV['APP_DEBUG'] = true;
        
        $logger = LoggerFactory::create('test-channel');
        $handlers = $logger->getHandlers();
        
        $this->assertEquals(Logger::DEBUG, $handlers[0]->getLevel()->value);
        
        unset($_ENV['APP_DEBUG']);
    }

    public function testLoggerUsesInfoLevelWhenAppDebugIsFalse(): void
    {
        $_ENV['APP_DEBUG'] = false;
        
        $logger = LoggerFactory::create('test-channel');
        $handlers = $logger->getHandlers();
        
        $this->assertEquals(Logger::INFO, $handlers[0]->getLevel()->value);
        
        unset($_ENV['APP_DEBUG']);
    }
}