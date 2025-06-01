<?php

declare(strict_types=1);

namespace PodcastPublisher\Tests\Resource\App\Feed;

use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use PodcastPublisher\Tests\Module\TestAppModule;
use PodcastPublisher\Resource\App\Feed\Generate;
use PodcastPublisher\Tests\Fake\FakeStorageClient;
use PodcastPublisher\Tests\Fake\FakePodcastFeedGenerator;
use Google\Cloud\Storage\StorageClient;
use PodcastPublisher\Contracts\PodcastFeedGeneratorInterface;

/**
 * @covers \PodcastPublisher\Resource\App\Feed\Generate
 * @covers \PodcastPublisher\Service\PodcastService
 */
final class GenerateTest extends TestCase
{
    private ResourceInterface $resource;
    private FakeStorageClient $storageClient;
    private FakePodcastFeedGenerator $feedGenerator;
    
    protected function setUp(): void
    {
        $this->storageClient = new FakeStorageClient();
        $this->feedGenerator = new FakePodcastFeedGenerator();
        
        $module = new class($this->storageClient, $this->feedGenerator) extends AbstractModule {
            public function __construct(
                private StorageClient $storageClient,
                private PodcastFeedGeneratorInterface $feedGenerator
            ) {
                parent::__construct();
            }
            
            protected function configure(): void
            {
                $this->install(new TestAppModule());
                $this->bind(StorageClient::class)->toInstance($this->storageClient);
                $this->bind(PodcastFeedGeneratorInterface::class)->toInstance($this->feedGenerator);
                
                // Bind additional podcast bucket name
                $this->bind()->annotatedWith('podcast.bucket')->toInstance('podcast-bucket');
            }
        };
        
        $injector = new Injector($module);
        $this->resource = $injector->getInstance(ResourceInterface::class);
        
        // Setup test buckets
        $this->storageClient->bucket('test-bucket');
        $this->storageClient->bucket('podcast-bucket');
    }
    
    public function testOnPostNoAudioFiles(): void
    {
        /** @var Generate $generate */
        $generate = $this->resource->post('app://self/feed/generate');
        
        $this->assertEquals(404, $generate->code);
        $this->assertFalse($generate->body['success']);
        $this->assertEquals(0, $generate->body['result']['episodes']);
        $this->assertEquals('No audio files found to generate feed', $generate->body['error']);
        
        // Check HAL links
        $this->assertArrayHasKey('_links', $generate->body);
        $this->assertEquals('/feed/generate', $generate->body['_links']['self']['href']);
        $this->assertEquals('/feed', $generate->body['_links']['feed']['href']);
    }
    
    public function testOnPostSuccess(): void
    {
        // Add audio files
        $bucket = $this->storageClient->bucket('test-bucket');
        
        $bucket->object('audio/2024/01/20/123.mp3')->upload('audio content', [
            'metadata' => [
                'bookmark_id' => '123',
                'title' => 'First Episode',
                'duration' => '300',
                'created_at' => '2024-01-20T10:00:00+00:00'
            ]
        ]);
        
        $bucket->object('audio/2024/01/21/456.mp3')->upload('audio content 2', [
            'metadata' => [
                'bookmark_id' => '456',
                'title' => 'Second Episode',
                'duration' => '600',
                'created_at' => '2024-01-21T10:00:00+00:00'
            ]
        ]);
        
        /** @var Generate $generate */
        $generate = $this->resource->post('app://self/feed/generate');
        
        $this->assertEquals(201, $generate->code);
        $this->assertTrue($generate->body['success']);
        $this->assertTrue($generate->body['result']['generated']);
        $this->assertEquals(2, $generate->body['result']['episodes']);
        $this->assertEquals('https://storage.googleapis.com/podcast-bucket/feed.xml', $generate->body['result']['url']);
        $this->assertArrayHasKey('timestamp', $generate->body);
        
        // Verify feed was created in podcast bucket
        $feedObject = $this->storageClient->bucket('podcast-bucket')->object('feed.xml');
        $this->assertTrue($feedObject->exists());
    }
    
    public function testOnPostWithExistingFeed(): void
    {
        // Add existing feed
        $podcastBucket = $this->storageClient->bucket('podcast-bucket');
        $podcastBucket->object('feed.xml')->upload('old feed content');
        
        // Add new audio file
        $bucket = $this->storageClient->bucket('test-bucket');
        $bucket->object('audio/new.mp3')->upload('audio', [
            'metadata' => [
                'bookmark_id' => '789',
                'title' => 'New Episode',
                'duration' => '300',
                'created_at' => '2024-01-22T10:00:00+00:00'
            ]
        ]);
        
        /** @var Generate $generate */
        $generate = $this->resource->post('app://self/feed/generate');
        
        $this->assertEquals(201, $generate->code);
        $this->assertTrue($generate->body['success']);
        
        // Verify feed was overwritten
        $feedContent = $podcastBucket->object('feed.xml')->downloadAsString();
        $this->assertNotEquals('old feed content', $feedContent);
    }
    
    public function testOnPostStorageError(): void
    {
        $this->storageClient->shouldThrowException(true);
        
        /** @var Generate $generate */
        $generate = $this->resource->post('app://self/feed/generate');
        
        $this->assertEquals(500, $generate->code);
        $this->assertFalse($generate->body['success']);
        $this->assertArrayHasKey('error', $generate->body);
        $this->assertEquals('Storage error', $generate->body['error']);
        $this->assertArrayHasKey('timestamp', $generate->body);
    }
    
    public function testResourceIsInjectable(): void
    {
        $this->markTestSkipped('Resource injection test requires full BEAR setup');
    }
}