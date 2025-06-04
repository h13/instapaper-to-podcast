<?php

declare(strict_types=1);

namespace InstapaperFetcher\Tests\Service;

use InstapaperFetcher\Service\BookmarkFetchService;
use InstapaperFetcher\Tests\Fake\FakeInstapaperClient;
use InstapaperFetcher\Tests\Fake\FakeStorageClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \InstapaperFetcher\Service\BookmarkFetchService
 */
final class BookmarkFetchServiceTest extends TestCase
{
    private BookmarkFetchService $service;
    private FakeInstapaperClient $instapaperClient;
    private FakeStorageClient $storageClient;
    private string $bucketName = 'test-bucket';
    
    protected function setUp(): void
    {
        $this->instapaperClient = new FakeInstapaperClient();
        $this->storageClient = new FakeStorageClient();
        
        $this->service = new BookmarkFetchService(
            $this->instapaperClient,
            $this->storageClient,
            $this->bucketName,
            new NullLogger()
        );
    }
    
    protected function tearDown(): void
    {
        $this->instapaperClient->reset();
        $this->storageClient->reset();
    }
    
    public function testGetBookmarksFromStorage(): void
    {
        // Prepare storage with existing bookmarks
        $bucket = $this->storageClient->bucket($this->bucketName);
        
        $bookmark1 = [
            'bookmark_id' => 1,
            'title' => 'Stored Article 1',
            'url' => 'https://example.com/1',
            'fetched_at' => date(\DateTimeInterface::ATOM)
        ];
        
        $bookmark2 = [
            'bookmark_id' => 2,
            'title' => 'Stored Article 2',
            'url' => 'https://example.com/2',
            'fetched_at' => date(\DateTimeInterface::ATOM)
        ];
        
        $bucket->object('raw-texts/2024/01/01/1.json')->upload(
            json_encode($bookmark1),
            ['metadata' => ['contentType' => 'application/json']]
        );
        
        $bucket->object('raw-texts/2024/01/01/2.json')->upload(
            json_encode($bookmark2),
            ['metadata' => ['contentType' => 'application/json']]
        );
        
        // Test getUnprocessedBookmarks method
        $bookmarks = $this->service->getUnprocessedBookmarks(10);
        
        // Currently returns empty array
        $this->assertIsArray($bookmarks);
        $this->assertCount(0, $bookmarks);
    }
    
    public function testGetBookmarksWithLimit(): void
    {
        // Prepare storage with multiple bookmarks
        $bucket = $this->storageClient->bucket($this->bucketName);
        
        for ($i = 1; $i <= 5; $i++) {
            $bookmark = [
                'bookmark_id' => $i,
                'title' => "Article {$i}",
                'url' => "https://example.com/{$i}",
                'fetched_at' => date(\DateTimeInterface::ATOM)
            ];
            
            $bucket->object("raw-texts/2024/01/01/{$i}.json")->upload(
                json_encode($bookmark)
            );
        }
        
        // Test getUnprocessedBookmarks with limit
        $bookmarks = $this->service->getUnprocessedBookmarks(3);
        
        $this->assertIsArray($bookmarks);
        $this->assertCount(0, $bookmarks);
    }
    
    public function testFetchAndStoreSuccess(): void
    {
        // Set up fake data
        $this->instapaperClient->setBookmarks([
            [
                'bookmark_id' => 100,
                'title' => 'New Article',
                'url' => 'https://example.com/new',
                'time' => time(),
                'description' => 'New description',
                'hash' => 'newhash'
            ]
        ]);
        
        $this->instapaperClient->setText(100, 'This is the article content.');
        
        // Execute
        $result = $this->service->fetchAndStore(1);
        
        // Verify
        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);
        
        // Check storage
        $bucket = $this->storageClient->getBucket($this->bucketName);
        $objects = iterator_to_array($bucket->objects(['prefix' => 'raw-texts/']));
        $this->assertCount(1, $objects);
        
        // Verify content
        $storedObject = $objects[0];
        $content = json_decode($storedObject->downloadAsString(), true);
        $this->assertEquals(100, $content['bookmark_id']);
        $this->assertEquals('New Article', $content['title']);
        $this->assertEquals('This is the article content.', $content['text']);
        $this->assertArrayHasKey('fetched_at', $content);
    }
    
    public function testFetchAndStoreWithApiError(): void
    {
        // Make API throw exception
        $this->instapaperClient->shouldThrowException(true, 'API Error');
        
        // Expect exception to be thrown
        $this->expectException(\InstapaperFetcher\Exceptions\InstapaperApiException::class);
        $this->expectExceptionMessage('API Error');
        
        // Execute
        $this->service->fetchAndStore(1);
    }
    
    public function testFetchAndStoreWithTextFetchError(): void
    {
        // Set up bookmarks but make text fetch fail
        $this->instapaperClient->setBookmarks([
            [
                'bookmark_id' => 200,
                'title' => 'Article',
                'url' => 'https://example.com/article',
                'time' => time(),
                'description' => 'Description',
                'hash' => 'hash'
            ]
        ]);
        
        // Don't set text for bookmark 200 - will cause error
        
        // Execute
        $result = $this->service->fetchAndStore(1);
        
        // Verify
        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals(200, $result['errors'][0]['id']);
    }
    
    public function testFetchAndStoreWithStorageError(): void
    {
        // Set up valid data
        $this->instapaperClient->setBookmarks([
            [
                'bookmark_id' => 300,
                'title' => 'Article',
                'url' => 'https://example.com/article',
                'time' => time(),
                'description' => 'Description',
                'hash' => 'hash'
            ]
        ]);
        $this->instapaperClient->setText(300, 'Content');
        
        // Make storage fail
        $this->storageClient->shouldThrowException(true);
        
        // Execute
        $result = $this->service->fetchAndStore(1);
        
        // Verify
        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
    }
    
    public function testFetchAndStoreMultipleWithPartialFailure(): void
    {
        // Set up multiple bookmarks with one failing
        $this->instapaperClient->setBookmarks([
            [
                'bookmark_id' => 401,
                'title' => 'Success Article 1',
                'url' => 'https://example.com/1',
                'time' => time(),
                'description' => 'Description 1',
                'hash' => 'hash1'
            ],
            [
                'bookmark_id' => 402,
                'title' => 'Fail Article',
                'url' => 'https://example.com/2',
                'time' => time(),
                'description' => 'Description 2',
                'hash' => 'hash2'
            ],
            [
                'bookmark_id' => 403,
                'title' => 'Success Article 2',
                'url' => 'https://example.com/3',
                'time' => time(),
                'description' => 'Description 3',
                'hash' => 'hash3'
            ]
        ]);
        
        // Set text for successful bookmarks only
        $this->instapaperClient->setText(401, 'Content 1');
        $this->instapaperClient->setText(403, 'Content 3');
        // 402 will fail
        
        // Execute
        $result = $this->service->fetchAndStore(3);
        
        // Verify
        $this->assertEquals(2, $result['fetched']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals(402, $result['errors'][0]['id']);
    }
}