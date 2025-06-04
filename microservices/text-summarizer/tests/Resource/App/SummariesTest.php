<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Resource\App;

use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;
use TextSummarizer\Resource\App\Summaries;
use TextSummarizer\Tests\Fake\FakeSummarizationService;

/**
 * @covers \TextSummarizer\Resource\App\Summaries
 */
final class SummariesTest extends TestCase
{
    private FakeSummarizationService $service;
    private Summaries $resource;

    protected function setUp(): void
    {
        $this->service = new FakeSummarizationService();
        $this->resource = new Summaries($this->service);
    }

    public function testIsResourceObject(): void
    {
        $this->assertInstanceOf(ResourceObject::class, $this->resource);
    }

    public function testOnGetWithDefaultLimit(): void
    {
        $expectedSummaries = [
            ['bookmark_id' => 1, 'title' => 'Test 1'],
            ['bookmark_id' => 2, 'title' => 'Test 2'],
        ];

        $this->service->setSummaries($expectedSummaries);

        $this->resource->onGet();

        $this->assertEquals(200, $this->resource->code);
        $this->assertArrayHasKey('summaries', $this->resource->body);
        $this->assertEquals($expectedSummaries, $this->resource->body['summaries']);
    }

    public function testOnGetWithCustomLimit(): void
    {
        $expectedSummaries = [
            ['bookmark_id' => 1, 'title' => 'Test 1'],
            ['bookmark_id' => 2, 'title' => 'Test 2'],
            ['bookmark_id' => 3, 'title' => 'Test 3'],
            ['bookmark_id' => 4, 'title' => 'Test 4'],
            ['bookmark_id' => 5, 'title' => 'Test 5'],
        ];

        $this->service->setSummaries($expectedSummaries);

        $this->resource->onGet(5);

        $this->assertEquals(200, $this->resource->code);
        $this->assertArrayHasKey('summaries', $this->resource->body);
        $this->assertEquals($expectedSummaries, $this->resource->body['summaries']);
    }

    public function testOnGetWithNoSummaries(): void
    {
        $this->service->setSummaries([]);

        $this->resource->onGet();

        $this->assertEquals(200, $this->resource->code);
        $this->assertArrayHasKey('summaries', $this->resource->body);
        $this->assertEquals([], $this->resource->body['summaries']);
    }

    public function testOnGetWithServiceException(): void
    {
        $this->service->shouldThrowException(true, 'Storage error');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Storage error');

        $this->resource->onGet();
    }
}