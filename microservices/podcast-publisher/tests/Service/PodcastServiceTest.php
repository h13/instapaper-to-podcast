<?php

declare(strict_types=1);

namespace PodcastPublisher\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use PodcastPublisher\Service\PodcastService;
use PodcastPublisher\Tests\Fake\FakeStorageClient;
use PodcastPublisher\Tests\Fake\FakePodcastFeedGenerator;

/**
 * @covers \PodcastPublisher\Service\PodcastService
 */
final class PodcastServiceTest extends TestCase
{
    private PodcastService $service;
    private FakePodcastFeedGenerator $feedGenerator;
    private FakeStorageClient $storageClient;
    private string $bucketName = 'test-bucket';
    private string $podcastBucketName = 'podcast-bucket';
    
    protected function setUp(): void
    {
        $this->feedGenerator = new FakePodcastFeedGenerator();
        $this->storageClient = new FakeStorageClient();
        
        $this->service = new PodcastService(
            $this->feedGenerator,
            $this->storageClient,
            $this->bucketName,
            $this->podcastBucketName,
            new NullLogger()
        );
        
        // Setup test buckets
        $this->storageClient->bucket($this->bucketName);
        $this->storageClient->bucket($this->podcastBucketName);
    }
    
    public function testGetFeedInfoWhenFeedDoesNotExist(): void
    {
        $info = $this->service->getFeedInfo();
        
        $this->assertFalse($info['exists']);
        $this->assertEquals(0, $info['episodes']);
        $this->assertNull($info['last_updated']);
    }
    
    public function testGetFeedInfoWhenFeedExists(): void
    {
        $bucket = $this->storageClient->bucket($this->podcastBucketName);
        
        // Create a fake feed
        $feedContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Podcast</title>
        <item><title>Episode 1</title></item>
        <item><title>Episode 2</title></item>
    </channel>
</rss>
XML;
        
        $bucket->object('feed.xml')->upload($feedContent);
        
        $info = $this->service->getFeedInfo();
        
        $this->assertTrue($info['exists']);
        $this->assertEquals(2, $info['episodes']);
        $this->assertNotNull($info['last_updated']);
        $this->assertEquals(strlen($feedContent), $info['size']);
        $this->assertEquals('https://storage.googleapis.com/podcast-bucket/feed.xml', $info['url']);
    }
    
    public function testGenerateFeedWithNoAudioFiles(): void
    {
        $result = $this->service->generateFeed();
        
        $this->assertFalse($result['generated']);
        $this->assertEquals(0, $result['episodes']);
        $this->assertEquals('', $result['url']);
    }
    
    public function testGenerateFeedWithAudioFiles(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        
        // Add audio files
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
        
        // Add summaries for descriptions
        $bucket->object('summaries/2024/01/20/123.json')->upload(
            json_encode(['summary' => 'Summary of first episode'])
        );
        $bucket->object('summaries/2024/01/21/456.json')->upload(
            json_encode(['summary' => 'Summary of second episode'])
        );
        
        $result = $this->service->generateFeed();
        
        $this->assertTrue($result['generated']);
        $this->assertEquals(2, $result['episodes']);
        $this->assertEquals('https://storage.googleapis.com/podcast-bucket/feed.xml', $result['url']);
        
        // Verify feed was stored
        $feedObject = $this->storageClient->bucket($this->podcastBucketName)->object('feed.xml');
        $this->assertTrue($feedObject->exists());
        
        // Verify episodes were passed to feed generator
        $lastFeed = $this->feedGenerator->getLastGeneratedFeed();
        $this->assertCount(2, $lastFeed['episodes']);
        $this->assertEquals('456', $lastFeed['episodes'][0]['bookmark_id']); // Newest first
        $this->assertEquals('123', $lastFeed['episodes'][1]['bookmark_id']);
    }
    
    public function testGenerateFeedSkipsNonMp3Files(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        
        // Add various files
        $bucket->object('audio/test.wav')->upload('wav content');
        $bucket->object('audio/test.txt')->upload('text content');
        $bucket->object('audio/test.mp3')->upload('mp3 content', [
            'metadata' => [
                'bookmark_id' => '789',
                'title' => 'Valid Episode',
                'duration' => '300',
                'created_at' => '2024-01-20T10:00:00+00:00'
            ]
        ]);
        
        $result = $this->service->generateFeed();
        
        $this->assertTrue($result['generated']);
        $this->assertEquals(1, $result['episodes']);
    }
    
    public function testGenerateFeedHandlesInvalidAudioMetadata(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        
        // Add audio with missing metadata
        $bucket->object('audio/invalid.mp3')->upload('content');
        
        // Add valid audio
        $bucket->object('audio/valid.mp3')->upload('content', [
            'metadata' => [
                'bookmark_id' => '999',
                'title' => 'Valid Episode',
                'duration' => '300',
                'created_at' => '2024-01-20T10:00:00+00:00'
            ]
        ]);
        
        $result = $this->service->generateFeed();
        
        $this->assertTrue($result['generated']);
        $this->assertEquals(2, $result['episodes']); // Both are included
        
        // Check that invalid one has default values
        $episodes = $this->feedGenerator->getLastGeneratedFeed()['episodes'];
        $invalidEpisode = array_filter($episodes, fn($e) => $e['bookmark_id'] === '');
        $this->assertNotEmpty($invalidEpisode);
        $invalidEpisode = reset($invalidEpisode);
        $this->assertEquals('Untitled', $invalidEpisode['title']);
        $this->assertEquals('No description available.', $invalidEpisode['description']);
    }
    
    public function testEpisodeSorting(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        
        // Add episodes in random order
        $dates = [
            '2024-01-15T10:00:00+00:00',
            '2024-01-25T10:00:00+00:00',
            '2024-01-20T10:00:00+00:00'
        ];
        
        foreach ($dates as $i => $date) {
            $bucket->object("audio/episode{$i}.mp3")->upload('content', [
                'metadata' => [
                    'bookmark_id' => (string)$i,
                    'title' => "Episode {$i}",
                    'duration' => '300',
                    'created_at' => $date
                ]
            ]);
        }
        
        $result = $this->service->generateFeed();
        
        $episodes = $this->feedGenerator->getLastGeneratedFeed()['episodes'];
        
        // Verify episodes are sorted by date (newest first)
        $this->assertEquals('1', $episodes[0]['bookmark_id']); // Jan 25
        $this->assertEquals('2', $episodes[1]['bookmark_id']); // Jan 20
        $this->assertEquals('0', $episodes[2]['bookmark_id']); // Jan 15
    }
    
    public function testStorageError(): void
    {
        $this->storageClient->shouldThrowException(true);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Storage error');
        
        $this->service->generateFeed();
    }
    
    public function testFeedGeneratorError(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        
        // Add an audio file
        $bucket->object('audio/test.mp3')->upload('content', [
            'metadata' => [
                'bookmark_id' => '123',
                'title' => 'Test Episode',
                'duration' => '300',
                'created_at' => '2024-01-20T10:00:00+00:00'
            ]
        ]);
        
        // Make feed generator throw exception
        $this->feedGenerator->shouldThrowException(true, 'Feed generation error');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Feed generation error');
        
        $this->service->generateFeed();
    }
    
    public function testEpisodeLimit(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        
        // Add more than 100 audio files
        for ($i = 1; $i <= 105; $i++) {
            $bucket->object("audio/{$i}.mp3")->upload('content', [
                'metadata' => [
                    'bookmark_id' => (string)$i,
                    'title' => "Episode {$i}",
                    'duration' => '300',
                    'created_at' => '2024-01-20T10:00:00+00:00'
                ]
            ]);
        }
        
        $result = $this->service->generateFeed();
        
        // Should be limited to 100 episodes
        $episodes = $this->feedGenerator->getLastGeneratedFeed()['episodes'];
        $this->assertCount(100, $episodes);
    }
    
    public function testAudioUrlGeneration(): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        
        $bucket->object('audio/2024/01/20/123.mp3')->upload('content', [
            'metadata' => [
                'bookmark_id' => '123',
                'title' => 'Test Episode',
                'duration' => '300',
                'created_at' => '2024-01-20T10:00:00+00:00'
            ]
        ]);
        
        $this->service->generateFeed();
        
        $episodes = $this->feedGenerator->getLastGeneratedFeed()['episodes'];
        $this->assertEquals(
            'https://storage.googleapis.com/test-bucket/audio/2024/01/20/123.mp3',
            $episodes[0]['audio_url']
        );
    }
}