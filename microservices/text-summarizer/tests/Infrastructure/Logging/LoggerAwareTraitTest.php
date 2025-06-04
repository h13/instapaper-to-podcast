<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Infrastructure\Logging;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TextSummarizer\Infrastructure\Logging\LoggerAwareTrait;

/**
 * @covers \TextSummarizer\Infrastructure\Logging\LoggerAwareTrait
 */
final class LoggerAwareTraitTest extends TestCase
{
    private $trait;
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->trait = new class($this->logger) {
            use LoggerAwareTrait;

            public function __construct(LoggerInterface $logger)
            {
                $this->logger = $logger;
            }

            public function getLogger(): ?LoggerInterface
            {
                return $this->logger;
            }
            
            public function callLogDebug(string $message, array $context = []): void
            {
                $this->logDebug($message, $context);
            }
            
            public function callLogInfo(string $message, array $context = []): void
            {
                $this->logInfo($message, $context);
            }
            
            public function callLogWarning(string $message, array $context = []): void
            {
                $this->logWarning($message, $context);
            }
            
            public function callLogError(string $message, array $context = []): void
            {
                $this->logError($message, $context);
            }
            
            public function callLogCritical(string $message, array $context = []): void
            {
                $this->logCritical($message, $context);
            }
        };
    }

    public function testLogDebug(): void
    {
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Debug message', ['key' => 'value']);

        $this->trait->callLogDebug('Debug message', ['key' => 'value']);
    }

    public function testLogInfo(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Info message', ['key' => 'value']);

        $this->trait->callLogInfo('Info message', ['key' => 'value']);
    }

    public function testLogWarning(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Warning message', ['key' => 'value']);

        $this->trait->callLogWarning('Warning message', ['key' => 'value']);
    }

    public function testLogError(): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error message', ['key' => 'value']);

        $this->trait->callLogError('Error message', ['key' => 'value']);
    }

    public function testLogCritical(): void
    {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Critical message', ['key' => 'value']);

        $this->trait->callLogCritical('Critical message', ['key' => 'value']);
    }
}