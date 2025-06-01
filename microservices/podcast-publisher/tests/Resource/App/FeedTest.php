<?php

declare(strict_types=1);

namespace PodcastPublisher\Tests\Resource\App;

use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use PodcastPublisher\Tests\Module\TestAppModule;
use PodcastPublisher\Resource\App\Feed;
use PodcastPublisher\Tests\Fake\FakeStorageClient;
use PodcastPublisher\Tests\Fake\FakePodcastFeedGenerator;
use Google\Cloud\Storage\StorageClient;
use PodcastPublisher\Contracts\PodcastFeedGeneratorInterface;

/**
 * @covers \PodcastPublisher\Resource\App\Feed
 * @covers \PodcastPublisher\Service\PodcastService
 */
final class FeedTest extends TestCase
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
    
    public function testOnGetWhenFeedDoesNotExist(): void
    {
        /** @var Feed $feed */
        $feed = $this->resource->get('app://self/feed');
        
        $this->assertInstanceOf(ResourceObject::class, $feed);
        $this->assertEquals(200, $feed->code);
        $this->assertFalse($feed->body['feed']['exists']);
        $this->assertEquals(0, $feed->body['feed']['episodes']);
        $this->assertNull($feed->body['feed']['last_updated']);
        
        // Check HAL links
        $this->assertArrayHasKey('_links', $feed->body);
        $this->assertEquals('/feed', $feed->body['_links']['self']['href']);
        $this->assertEquals('/feed/generate', $feed->body['_links']['generate']['href']);
    }
    
    public function testOnGetWhenFeedExists(): void
    {
        $bucket = $this->storageClient->bucket('podcast-bucket');
        
        // Create a fake feed
        $feedContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Podcast</title>
        <item><title>Episode 1</title></item>
        <item><title>Episode 2</title></item>
        <item><title>Episode 3</title></item>
    </channel>
</rss>
XML;
        
        $bucket->object('feed.xml')->upload($feedContent);
        
        /** @var Feed $feed */
        $feed = $this->resource->get('app://self/feed');
        
        $this->assertEquals(200, $feed->code);
        $this->assertTrue($feed->body['feed']['exists']);
        $this->assertEquals(3, $feed->body['feed']['episodes']);
        $this->assertNotNull($feed->body['feed']['last_updated']);
        $this->assertEquals(strlen($feedContent), $feed->body['feed']['size']);
        $this->assertEquals('https://storage.googleapis.com/podcast-bucket/feed.xml', $feed->body['feed']['url']);
    }
    
    public function testResourceIsInjectable(): void
    {
        $this->markTestSkipped('Resource injection test requires full BEAR setup');
    }
}