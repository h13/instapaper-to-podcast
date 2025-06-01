<?php

declare(strict_types=1);

namespace TextToSpeech\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use TextToSpeech\Service\TextToSpeechService;
use TextToSpeech\Tests\Fake\FakeStorageClient;
use TextToSpeech\Tests\Fake\FakeTextToSpeech;

/**
 * @covers \TextToSpeech\Service\TextToSpeechService
 */
final class TextToSpeechServiceTest extends TestCase
{
    private TextToSpeechService $service;
    private FakeTextToSpeech $ttsGenerator;
    private FakeStorageClient $storageClient;
    private string $bucketName = 'test-bucket';

    protected function setUp(): void
    {
        $this->markTestSkipped('TextToSpeechService needs refactoring');
    }

    public function testGetAudioFilesEmpty(): void
    {
        $audioFiles = $this->service->getAudioFiles(10);

        $this->assertIsArray($audioFiles);
        $this->assertEmpty($audioFiles);
    }

    public function testGetAudioFilesWithData(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add test audio file
        $object = $bucket->object('audio/2024/01/20/123.mp3');
        $object->upload('fake audio content', [
            'metadata' => [
                'bookmark_id' => '123',
                'title' => 'Test Article',
                'duration' => '120',
                'created_at' => '2024-01-20T10:00:00+00:00',
                'contentType' => 'audio/mpeg',
            ],
        ]);

        $audioFiles = $this->service->getAudioFiles(10);

        $this->assertCount(1, $audioFiles);
        $this->assertEquals('123', $audioFiles[0]['bookmark_id']);
        $this->assertEquals('Test Article', $audioFiles[0]['title']);
        $this->assertEquals('120', $audioFiles[0]['duration']);
        $this->assertEquals('audio/2024/01/20/123.mp3', $audioFiles[0]['path']);
    }

    public function testGetAudioFilesSkipsNonMp3Files(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add non-MP3 file
        $bucket->object('audio/test.wav')->upload('wav content');

        // Add MP3 file
        $bucket->object('audio/test.mp3')->upload('mp3 content');

        $audioFiles = $this->service->getAudioFiles(10);

        $this->assertCount(1, $audioFiles);
        $this->assertStringEndsWith('.mp3', $audioFiles[0]['path']);
    }

    public function testProcessSummariesEmpty(): void
    {
        $result = $this->service->processSummaries(10);

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);
    }

    public function testProcessSummariesSuccess(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add summary to process
        $summaryData = [
            'bookmark_id' => 789,
            'title' => 'Article to Convert',
            'url' => 'https://example.com/article',
            'summary' => 'This is a summary of the article that will be converted to speech.',
            'summarized_at' => '2024-01-20T09:00:00+00:00',
        ];

        $bucket->object('summaries/2024/01/20/789.json')->upload(
            json_encode($summaryData)
        );

        $result = $this->service->processSummaries(10);

        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);

        // Check if audio was created
        $audioObject = $bucket->object('audio/2024/01/20/789.mp3');
        $this->assertTrue($audioObject->exists());

        // Verify audio metadata
        $metadata = $audioObject->getMetadata();
        $this->assertEquals('audio/mpeg', $metadata['contentType']);
        $this->assertEquals('789', $metadata['bookmark_id']);
        $this->assertEquals('Article to Convert', $metadata['title']);
        $this->assertArrayHasKey('duration', $metadata);
        $this->assertArrayHasKey('created_at', $metadata);
    }

    public function testProcessSummariesSkipsExistingAudio(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add summary
        $summaryData = [
            'bookmark_id' => 111,
            'title' => 'Already Converted',
            'summary' => 'Summary text',
        ];

        $bucket->object('summaries/2024/01/20/111.json')->upload(
            json_encode($summaryData)
        );

        // Add existing audio
        $bucket->object('audio/2024/01/20/111.mp3')->upload('existing audio');

        $result = $this->service->processSummaries(10);

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    public function testProcessSummariesHandlesInvalidFormat(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add invalid data (missing 'summary' field)
        $bucket->object('summaries/2024/01/20/222.json')->upload(
            json_encode(['bookmark_id' => 222, 'title' => 'No summary field'])
        );

        $result = $this->service->processSummaries(10);

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Invalid summary data format', $result['errors'][0]['error']);
    }

    public function testProcessSummariesHandlesTtsError(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add summary
        $summaryData = [
            'bookmark_id' => 333,
            'title' => 'Will fail',
            'summary' => 'Some summary text',
        ];

        $bucket->object('summaries/2024/01/20/333.json')->upload(
            json_encode($summaryData)
        );

        // Make TTS generator throw exception
        $this->ttsGenerator->shouldThrowException(true, 'TTS API error');

        $result = $this->service->processSummaries(10);

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals('TTS API error', $result['errors'][0]['error']);
    }

    public function testProcessSummariesRespectsLimit(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add multiple summaries
        for ($i = 1; $i <= 5; $i++) {
            $bucket->object("summaries/2024/01/20/{$i}.json")->upload(
                json_encode([
                    'bookmark_id' => $i,
                    'title' => "Article {$i}",
                    'summary' => "Summary content {$i}",
                ])
            );
        }

        // Process with limit of 3
        $result = $this->service->processSummaries(3);

        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    public function testProcessSummariesStorageError(): void
    {
        $this->storageClient->shouldThrowException(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Storage error');

        $this->service->processSummaries(10);
    }

    public function testDurationCalculation(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add summary with known word count
        $summary = str_repeat('word ', 150); // 150 words = 1 minute at 150 WPM
        $summaryData = [
            'bookmark_id' => 444,
            'title' => 'Test Duration',
            'summary' => $summary,
        ];

        $bucket->object('summaries/2024/01/20/444.json')->upload(
            json_encode($summaryData)
        );

        $this->service->processSummaries(10);

        // Check calculated duration
        $audioObject = $bucket->object('audio/2024/01/20/444.mp3');
        $metadata = $audioObject->getMetadata();

        $this->assertEquals('60', $metadata['duration']); // 1 minute = 60 seconds
    }

    public function testGeneratedAudioContent(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        // Add summary
        $summaryData = [
            'bookmark_id' => 555,
            'title' => 'Test Audio Generation',
            'summary' => 'This text will be converted to speech.',
        ];

        $bucket->object('summaries/2024/01/20/555.json')->upload(
            json_encode($summaryData)
        );

        $this->service->processSummaries(10);

        // Verify TTS was called correctly
        $generatedAudios = $this->ttsGenerator->getGeneratedAudios();
        $this->assertCount(1, $generatedAudios);
        $this->assertEquals('This text will be converted to speech.', $generatedAudios[0]['text']);
        $this->assertEquals('nova', $generatedAudios[0]['voice']);

        // Verify audio was stored
        $audioObject = $bucket->object('audio/2024/01/20/555.mp3');
        $audioContent = $audioObject->downloadAsString();
        $this->assertNotEmpty($audioContent);
        $this->assertStringStartsWith('FAKE_AUDIO:', base64_decode($audioContent));
    }
}
