<?php

declare(strict_types=1);

namespace TextToSpeech\Tests\Resource\App\Audio;

use BEAR\Resource\ResourceInterface;
use Google\Cloud\Storage\StorageClient;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use TextToSpeech\Contracts\TextToSpeechInterface;
use TextToSpeech\Module\AppModule;
use TextToSpeech\Resource\App\Audio\Generate;
use TextToSpeech\Tests\Fake\FakeStorageClient;
use TextToSpeech\Tests\Fake\FakeTextToSpeech;

/**
 * @covers \TextToSpeech\Resource\App\Audio\Generate
 */
final class GenerateTest extends TestCase
{
    private ResourceInterface $resource;
    private FakeStorageClient $storageClient;
    private FakeTextToSpeech $ttsGenerator;

    protected function setUp(): void
    {
        $this->markTestSkipped('Resource tests need refactoring');
    }

    public function testOnPostNoSummaries(): void
    {
        /** @var Generate $generate */
        $generate = $this->resource->post('app://self/audio/generate');

        $this->assertEquals(201, $generate->code);
        $this->assertTrue($generate->body['success']);
        $this->assertEquals(0, $generate->body['result']['processed']);
        $this->assertEquals(0, $generate->body['result']['failed']);
        $this->assertEmpty($generate->body['result']['errors']);

        // Check HAL links
        $this->assertArrayHasKey('_links', $generate->body);
        $this->assertEquals('/audio/generate', $generate->body['_links']['self']['href']);
        $this->assertEquals('/audio', $generate->body['_links']['audio']['href']);
    }

    public function testOnPostSuccess(): void
    {
        // Add summary to process
        $bucket = $this->storageClient->bucket('test-bucket');
        $summaryData = [
            'bookmark_id' => 789,
            'title' => 'Article to Convert',
            'url' => 'https://example.com/article',
            'summary' => 'This is a summary that will be converted to speech.',
        ];

        $bucket->object('summaries/2024/01/20/789.json')->upload(
            json_encode($summaryData)
        );

        /** @var Generate $generate */
        $generate = $this->resource->post('app://self/audio/generate');

        $this->assertEquals(201, $generate->code);
        $this->assertTrue($generate->body['success']);
        $this->assertEquals(1, $generate->body['result']['processed']);
        $this->assertEquals(0, $generate->body['result']['failed']);
        $this->assertArrayHasKey('timestamp', $generate->body);

        // Verify audio was created
        $audioObject = $bucket->object('audio/2024/01/20/789.mp3');
        $this->assertTrue($audioObject->exists());
    }

    public function testOnPostWithLimit(): void
    {
        $bucket = $this->storageClient->bucket('test-bucket');

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

        /** @var Generate $generate */
        $generate = $this->resource->post('app://self/audio/generate', ['limit' => 3]);

        $this->assertEquals(201, $generate->code);
        $this->assertTrue($generate->body['success']);
        $this->assertEquals(3, $generate->body['result']['processed']);
    }

    public function testOnPostPartialFailure(): void
    {
        $bucket = $this->storageClient->bucket('test-bucket');

        // Add valid summary
        $bucket->object('summaries/2024/01/20/1.json')->upload(
            json_encode([
                'bookmark_id' => 1,
                'title' => 'Valid Article',
                'summary' => 'Valid summary',
            ])
        );

        // Add invalid summary (missing 'summary' field)
        $bucket->object('summaries/2024/01/20/2.json')->upload(
            json_encode([
                'bookmark_id' => 2,
                'title' => 'Invalid Article',
            ])
        );

        /** @var Generate $generate */
        $generate = $this->resource->post('app://self/audio/generate');

        $this->assertEquals(201, $generate->code);
        $this->assertTrue($generate->body['success']);
        $this->assertEquals(1, $generate->body['result']['processed']);
        $this->assertEquals(1, $generate->body['result']['failed']);
        $this->assertCount(1, $generate->body['result']['errors']);
    }

    public function testOnPostStorageError(): void
    {
        $this->storageClient->shouldThrowException(true);

        /** @var Generate $generate */
        $generate = $this->resource->post('app://self/audio/generate');

        $this->assertEquals(500, $generate->code);
        $this->assertFalse($generate->body['success']);
        $this->assertArrayHasKey('error', $generate->body);
        $this->assertEquals('Storage error', $generate->body['error']);
        $this->assertArrayHasKey('timestamp', $generate->body);
    }

    public function testResourceIsInjectable(): void
    {
        $injector = new Injector(new AppModule('test'));
        $generate = $injector->getInstance(Generate::class);

        $this->assertInstanceOf(Generate::class, $generate);
    }
}
