<?php

declare(strict_types=1);

namespace InstapaperFetcher\Tests\Resource\App;

use BEAR\Package\Bootstrap;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use InstapaperFetcher\Resource\App\Bookmarks;
use InstapaperFetcher\Tests\Fake\FakeInstapaperClient;
use InstapaperFetcher\Tests\Fake\FakeStorageClient;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

final class BookmarksTest extends TestCase
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
    
    public function testOnGet(): void
    {
        // Prepare test data in storage
        $bucket = $this->fakeStorageClient->bucket('test-bucket');
        
        $bookmark = [
            'bookmark_id' => 1,
            'title' => 'Test Article',
            'url' => 'https://example.com/article',
            'fetched_at' => date(\DateTimeInterface::ATOM)
        ];
        
        $bucket->object('raw-texts/2024/01/01/1.json')->upload(
            json_encode($bookmark),
            ['metadata' => ['contentType' => 'application/json']]
        );
        
        // Execute resource
        $ro = $this->resource->get('/bookmarks', ['limit' => 10]);
        
        // Assert response
        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('bookmarks', $ro->body);
        $this->assertArrayHasKey('count', $ro->body);
        $this->assertArrayHasKey('_links', $ro->body);
        
        $this->assertCount(1, $ro->body['bookmarks']);
        $this->assertEquals('Test Article', $ro->body['bookmarks'][0]['title']);
        
        // Check HATEOAS links
        $this->assertArrayHasKey('self', $ro->body['_links']);
        $this->assertArrayHasKey('fetch', $ro->body['_links']);
        $this->assertEquals('/bookmarks?limit=10', $ro->body['_links']['self']['href']);
        $this->assertEquals('/bookmarks/fetch', $ro->body['_links']['fetch']['href']);
    }
    
    public function testOnGetWithDifferentLimit(): void
    {
        // Prepare multiple bookmarks
        $bucket = $this->fakeStorageClient->bucket('test-bucket');
        
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
        
        // Test with limit = 3
        $ro = $this->resource->get('/bookmarks', ['limit' => 3]);
        
        $this->assertSame(200, $ro->code);
        $this->assertCount(3, $ro->body['bookmarks']);
        $this->assertEquals(3, $ro->body['count']);
    }
    
    public function testOnGetEmptyResult(): void
    {
        // No data in storage
        $ro = $this->resource->get('/bookmarks');
        
        $this->assertSame(200, $ro->code);
        $this->assertCount(0, $ro->body['bookmarks']);
        $this->assertEquals(0, $ro->body['count']);
    }
}