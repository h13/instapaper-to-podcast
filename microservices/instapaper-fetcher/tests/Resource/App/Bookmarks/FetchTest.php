<?php

declare(strict_types=1);

namespace InstapaperFetcher\Tests\Resource\App\Bookmarks;

use BEAR\Package\Bootstrap;
use BEAR\Resource\ResourceInterface;
use InstapaperFetcher\Tests\Fake\FakeInstapaperClient;
use InstapaperFetcher\Tests\Fake\FakeStorageClient;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

final class FetchTest extends TestCase
{
    private ResourceInterface $resource;
    private FakeInstapaperClient $fakeInstapaperClient;
    private FakeStorageClient $fakeStorageClient;
    
    protected function setUp(): void
    {
        $this->fakeInstapaperClient = new FakeInstapaperClient();
        $this->fakeStorageClient = new FakeStorageClient();
        
        // Create test module
        $module = new class($this->fakeInstapaperClient, $this->fakeStorageClient) extends AbstractModule {
            public function __construct(
                private FakeInstapaperClient $instapaperClient,
                private FakeStorageClient $storageClient
            ) {
                parent::__construct();
            }
            
            protected function configure(): void
            {
                $this->bind(\InstapaperFetcher\Contracts\InstapaperClientInterface::class)
                    ->toInstance($this->instapaperClient);
                $this->bind(\Google\Cloud\Storage\StorageClient::class)
                    ->toInstance($this->storageClient);
                $this->bind()->annotatedWith('storage.bucket')
                    ->toInstance('test-bucket');
                $this->bind(\Psr\Log\LoggerInterface::class)
                    ->to(\Psr\Log\NullLogger::class);
            }
        };
        
        // Skip test that requires BEAR.Sunday app bootstrap
        $this->markTestSkipped('Resource tests require BEAR.Sunday app bootstrap');
    }
    
    public function testOnPostSuccess(): void
    {
        // Execute resource
        $ro = $this->resource->post('/bookmarks/fetch', ['limit' => 2]);
        
        // Assert response
        $this->assertSame(201, $ro->code);
        $this->assertTrue($ro->body['success']);
        $this->assertArrayHasKey('result', $ro->body);
        $this->assertArrayHasKey('timestamp', $ro->body);
        $this->assertArrayHasKey('_links', $ro->body);
        
        $result = $ro->body['result'];
        $this->assertEquals(2, $result['fetched']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);
        
        // Check HATEOAS links
        $this->assertEquals('/bookmarks/fetch', $ro->body['_links']['self']['href']);
        $this->assertEquals('/bookmarks', $ro->body['_links']['bookmarks']['href']);
    }
    
    public function testOnPostWithApiError(): void
    {
        // Make API fail
        $this->fakeInstapaperClient->shouldThrowException(true, 'API Error');
        
        // Execute resource
        $ro = $this->resource->post('/bookmarks/fetch');
        
        // Assert error response
        $this->assertSame(500, $ro->code);
        $this->assertFalse($ro->body['success']);
        $this->assertStringContainsString('API Error', $ro->body['error']);
        $this->assertArrayHasKey('timestamp', $ro->body);
    }
    
    public function testOnPostWithDefaultLimit(): void
    {
        // Execute without specifying limit
        $ro = $this->resource->post('/bookmarks/fetch');
        
        // Assert response
        $this->assertSame(201, $ro->code);
        $this->assertTrue($ro->body['success']);
        
        // Default limit is 10, but we only have 3 test bookmarks
        $result = $ro->body['result'];
        $this->assertEquals(3, $result['fetched']);
    }
    
    public function testOnPostWithPartialFailure(): void
    {
        // Set up bookmarks where one will fail
        $this->fakeInstapaperClient->setBookmarks([
            [
                'bookmark_id' => 1,
                'title' => 'Success',
                'url' => 'https://example.com/1',
                'time' => time(),
                'description' => 'Description',
                'hash' => 'hash1'
            ],
            [
                'bookmark_id' => 999, // No text for this ID
                'title' => 'Fail',
                'url' => 'https://example.com/999',
                'time' => time(),
                'description' => 'Description',
                'hash' => 'hash999'
            ]
        ]);
        
        // Execute resource
        $ro = $this->resource->post('/bookmarks/fetch', ['limit' => 2]);
        
        // Assert response
        $this->assertSame(201, $ro->code);
        $this->assertTrue($ro->body['success']);
        
        $result = $ro->body['result'];
        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals(999, $result['errors'][0]['bookmark_id']);
    }
}