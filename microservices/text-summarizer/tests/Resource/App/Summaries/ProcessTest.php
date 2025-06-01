<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Resource\App\Summaries;

use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TextSummarizer\Resource\App\Summaries\Process;
use TextSummarizer\Tests\Fake\FakeSummarizationService;

/**
 * @covers \TextSummarizer\Resource\App\Summaries\Process
 */
final class ProcessTest extends TestCase
{
    private FakeSummarizationService $service;
    private LoggerInterface $logger;
    private Process $resource;

    protected function setUp(): void
    {
        $this->service = new FakeSummarizationService();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resource = new Process($this->service, $this->logger);
    }

    public function testIsResourceObject(): void
    {
        $this->assertInstanceOf(ResourceObject::class, $this->resource);
    }

    public function testOnPostSuccess(): void
    {
        $processingResult = [
            'processed' => 5,
            'failed' => 0,
            'errors' => []
        ];

        $this->service->setProcessResult($processingResult);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->logicalOr(
                $this->stringContains('Starting text summarization'),
                $this->stringContains('Text summarization completed')
            ));

        $this->resource->onPost();

        $this->assertEquals(201, $this->resource->code);
        $this->assertTrue($this->resource->body['success']);
        $this->assertEquals($processingResult, $this->resource->body['result']);
    }

    public function testOnPostWithCustomLimit(): void
    {
        $processingResult = [
            'processed' => 3,
            'failed' => 2,
            'errors' => [
                ['bookmark_id' => 1, 'error' => 'API error'],
                ['bookmark_id' => 2, 'error' => 'Invalid format']
            ]
        ];

        $this->service->setProcessResult($processingResult);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->logicalOr(
                $this->stringContains('Starting text summarization'),
                $this->stringContains('Text summarization completed')
            ));

        $this->resource->onPost(5);

        $this->assertEquals(201, $this->resource->code);
        $this->assertTrue($this->resource->body['success']);
        $this->assertEquals($processingResult, $this->resource->body['result']);
    }

    public function testOnPostWithNoTextsToProcess(): void
    {
        $processingResult = [
            'processed' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $this->service->setProcessResult($processingResult);

        $this->logger->expects($this->once())
            ->method('info');

        $this->resource->onPost();

        $this->assertEquals(201, $this->resource->code);
        $this->assertTrue($this->resource->body['success']);
        $this->assertEquals($processingResult, $this->resource->body['result']);
    }

    public function testOnPostWithException(): void
    {
        $this->service->shouldThrowException(true, 'Storage connection failed');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Starting text summarization'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Failed to process texts'),
                $this->arrayHasKey('error')
            );

        $this->resource->onPost();

        $this->assertEquals(500, $this->resource->code);
        $this->assertArrayHasKey('error', $this->resource->body);
        $this->assertEquals('Storage connection failed', $this->resource->body['error']);
    }

    public function testOnPostWithAllFailures(): void
    {
        $processingResult = [
            'processed' => 0,
            'failed' => 3,
            'errors' => [
                ['bookmark_id' => 1, 'error' => 'API error'],
                ['bookmark_id' => 2, 'error' => 'Invalid format'],
                ['bookmark_id' => 3, 'error' => 'Network timeout']
            ]
        ];

        $this->service->setProcessResult($processingResult);

        $this->logger->expects($this->once())
            ->method('info');

        $this->resource->onPost();

        $this->assertEquals(201, $this->resource->code);
        $this->assertTrue($this->resource->body['success']);
        $this->assertEquals($processingResult, $this->resource->body['result']);
    }
}