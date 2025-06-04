<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use TextSummarizer\Service\SummarizationService;
use TextSummarizer\Tests\Fake\FakeStorageClient;
use TextSummarizer\Tests\Fake\FakeTextSummarizer;

/**
 * @covers \TextSummarizer\Service\SummarizationService
 */
final class SummarizationServiceTest extends TestCase
{
    private SummarizationService $service;
    private FakeTextSummarizer $summarizer;
    private FakeStorageClient $storageClient;
    private string $bucketName = 'test-bucket';

    protected function setUp(): void
    {
        $this->summarizer = new FakeTextSummarizer();
        $this->storageClient = new FakeStorageClient();

        $this->service = new SummarizationService(
            $this->summarizer,
            $this->storageClient,
            $this->bucketName,
            new NullLogger()
        );

        // Setup test bucket
        $bucket = $this->storageClient->bucket($this->bucketName);
    }

    public function testGetSummariesEmpty(): void
    {
        $summaries = $this->service->getSummaries(10);

        $this->assertIsArray($summaries);
        $this->assertEmpty($summaries);
    }

    public function testGetSummariesWithData(): void
    {
        // Prepare test data
        $bucket = $this->storageClient->bucket($this->bucketName);

        $summaryData = [
            'bookmark_id' => 123,
            'title' => 'Test Article',
            'url' => 'https://example.com/article',
            'text' => 'Original text',
            'summary' => 'Summary of the article',
            'summarized_at' => '2024-01-20T10:00:00+00:00',
        ];

        $bucket->object('summaries/2024/01/20/123.json')->upload(
            json_encode($summaryData)
        );

        $summaries = $this->service->getSummaries(10);

        $this->assertCount(1, $summaries);
        $this->assertEquals(123, $summaries[0]['bookmark_id']);
        $this->assertEquals('Test Article', $summaries[0]['title']);
        $this->assertEquals('2024-01-20T10:00:00+00:00', $summaries[0]['summarized_at']);
        $this->assertEquals('summaries/2024/01/20/123.json', $summaries[0]['path']);
    }

    public function testGetSummariesSkipsNonJsonFiles(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add non-JSON file
        $bucket->object('summaries/test.txt')->upload('text content');

        // Add JSON file
        $bucket->object('summaries/123.json')->upload(
            json_encode(['bookmark_id' => 123, 'title' => 'Test'])
        );

        $summaries = $this->service->getSummaries(10);

        $this->assertCount(1, $summaries);
        $this->assertEquals(123, $summaries[0]['bookmark_id']);
    }

    public function testGetSummariesHandlesInvalidJson(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add invalid JSON
        $bucket->object('summaries/invalid.json')->upload('invalid json');

        // Add valid JSON
        $bucket->object('summaries/valid.json')->upload(
            json_encode(['bookmark_id' => 456, 'title' => 'Valid'])
        );

        $summaries = $this->service->getSummaries(10);

        $this->assertCount(1, $summaries);
        $this->assertEquals(456, $summaries[0]['bookmark_id']);
    }

    public function testProcessTextsEmpty(): void
    {
        $result = $this->service->processTexts(10);

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);
    }

    public function testProcessTextsSuccess(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add raw text to process
        $rawData = [
            'bookmark_id' => 789,
            'title' => 'Article to Summarize',
            'url' => 'https://example.com/article',
            'text' => 'This is a long article about technology and innovation.',
            'fetched_at' => '2024-01-20T09:00:00+00:00',
        ];

        $bucket->object('raw-texts/2024/01/20/789.json')->upload(
            json_encode($rawData)
        );

        $result = $this->service->processTexts(10);

        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);

        // Check if summary was created - use current date
        $summaryPath = sprintf('summaries/%s/789.json', date('Y/m/d'));
        $summaryObject = $bucket->object($summaryPath);
        $this->assertTrue($summaryObject->exists());

        $summaryData = json_decode($summaryObject->downloadAsString(), true);
        $this->assertArrayHasKey('summary', $summaryData);
        $this->assertArrayHasKey('summarized_at', $summaryData);
        $this->assertEquals('summarized', $summaryData['status']);
    }

    public function testProcessTextsSkipsAlreadySummarized(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add raw text
        $rawData = [
            'bookmark_id' => 111,
            'title' => 'Already Summarized',
            'text' => 'Text content',
        ];

        $bucket->object('raw-texts/2024/01/20/111.json')->upload(
            json_encode($rawData)
        );

        // Add existing summary
        $bucket->object('summaries/2024/01/20/111.json')->upload(
            json_encode(['bookmark_id' => 111, 'summary' => 'Existing summary'])
        );

        $result = $this->service->processTexts(10);

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    public function testProcessTextsHandlesInvalidFormat(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add invalid data (missing 'text' field)
        $bucket->object('raw-texts/2024/01/20/222.json')->upload(
            json_encode(['bookmark_id' => 222, 'title' => 'No text field'])
        );

        $result = $this->service->processTexts(10);

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Invalid bookmark data format', $result['errors'][0]['error']);
    }

    public function testProcessTextsHandlesSummarizationError(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add raw text
        $rawData = [
            'bookmark_id' => 333,
            'title' => 'Will fail',
            'text' => 'Some text',
        ];

        $bucket->object('raw-texts/2024/01/20/333.json')->upload(
            json_encode($rawData)
        );

        // Make summarizer throw exception
        $this->summarizer->shouldThrowException(true, 'API error');

        $result = $this->service->processTexts(10);

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals('API error', $result['errors'][0]['error']);
    }

    public function testProcessTextsRespectsLimit(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add multiple raw texts
        for ($i = 1; $i <= 5; $i++) {
            $bucket->object("raw-texts/2024/01/20/{$i}.json")->upload(
                json_encode([
                    'bookmark_id' => $i,
                    'title' => "Article {$i}",
                    'text' => "Text content {$i}",
                ])
            );
        }

        // Process with limit of 3
        $result = $this->service->processTexts(3);

        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    public function testProcessTextsStorageError(): void
    {
        $this->storageClient->shouldThrowException(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Storage error');

        $this->service->processTexts(10);
    }

    public function testStoreSummaryMetadata(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add raw text
        $rawData = [
            'bookmark_id' => 444,
            'title' => 'Test Metadata',
            'url' => 'https://example.com',
            'text' => 'Content to summarize',
        ];

        $bucket->object('raw-texts/2024/01/20/444.json')->upload(
            json_encode($rawData)
        );

        $this->service->processTexts(10);

        // Check metadata - use current date
        $summaryPath = sprintf('summaries/%s/444.json', date('Y/m/d'));
        $summaryObject = $bucket->object($summaryPath);
        $metadata = $summaryObject->getMetadata();

        $this->assertEquals('application/json', $metadata['contentType']);
        $this->assertEquals('444', $metadata['bookmark_id']);
        $this->assertEquals('Test Metadata', $metadata['title']);
        $this->assertEquals('summarized', $metadata['status']);
    }

    public function testGetSummariesHandlesObjectError(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add a file that will fail when downloading
        $bucket->object('summaries/2024/01/20/555.json')->upload('{"bookmark_id": 555}');
        
        // Make the object throw exception on downloadAsString
        $bucket->object('summaries/2024/01/20/555.json')->shouldThrowOnDownload = true;

        $summaries = $this->service->getSummaries(10);

        // Should return empty array when object download fails
        $this->assertIsArray($summaries);
        $this->assertEmpty($summaries);
    }

    public function testIsSummarizedHandlesJsonException(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Create a file with invalid JSON
        $bucket->object('summaries/2024/01/20/666.json')->upload('invalid json content');

        // This should not throw exception, just return false
        $result = $this->service->processTexts(10);
        
        // The bookmark should not be considered as already summarized
        $this->assertEquals(0, $result['processed']);
    }
}
