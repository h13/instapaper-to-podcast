<?php

declare(strict_types=1);

namespace InstapaperFetcher\Tests\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InstapaperFetcher\Exceptions\InstapaperApiException;
use InstapaperFetcher\Service\InstapaperClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \InstapaperFetcher\Service\InstapaperClient
 */
final class InstapaperClientTest extends TestCase
{
    private InstapaperClient $client;
    private MockHandler $mockHandler;
    
    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        
        // Create client with mock handler
        $config = [
            'consumer_key' => 'test_key',
            'consumer_secret' => 'test_secret',
            'token' => 'test_token',
            'token_secret' => 'test_token_secret'
        ];
        
        $httpClient = new Client(['handler' => $handlerStack]);
        $this->client = new InstapaperClient($config, $httpClient, new NullLogger());
    }
    
    public function testGetBookmarksSuccess(): void
    {
        $expectedResponse = [
            [
                'type' => 'bookmark',
                'bookmark_id' => 123,
                'title' => 'Test Article',
                'url' => 'https://example.com/article',
                'time' => time(),
                'description' => 'Test description',
                'hash' => 'testhash'
            ]
        ];
        
        $this->mockHandler->append(
            new Response(200, [], json_encode($expectedResponse))
        );
        
        $bookmarks = $this->client->getBookmarks(10, 'unread');
        
        $this->assertCount(1, $bookmarks);
        $this->assertEquals(123, $bookmarks[0]['bookmark_id']);
        $this->assertEquals('Test Article', $bookmarks[0]['title']);
    }
    
    public function testGetBookmarksApiError(): void
    {
        $this->mockHandler->append(
            new Response(401, [], 'Unauthorized')
        );
        
        $this->expectException(InstapaperApiException::class);
        $this->expectExceptionMessage('Failed to fetch bookmarks');
        
        $this->client->getBookmarks();
    }
    
    public function testGetBookmarksNetworkError(): void
    {
        $this->mockHandler->append(
            new ClientException(
                'Network error',
                new Request('GET', 'test'),
                new Response(500)
            )
        );
        
        $this->expectException(InstapaperApiException::class);
        
        $this->client->getBookmarks();
    }
    
    public function testGetTextSuccess(): void
    {
        $expectedText = 'This is the full article text.';
        
        $this->mockHandler->append(
            new Response(200, [], $expectedText)
        );
        
        $text = $this->client->getText(123);
        
        $this->assertEquals($expectedText, $text);
    }
    
    public function testGetTextNotFound(): void
    {
        $this->mockHandler->append(
            new Response(404, [], 'Not found')
        );
        
        $this->expectException(InstapaperApiException::class);
        $this->expectExceptionMessage('Failed to fetch text for bookmark 999');
        
        $this->client->getText(999);
    }
    
    public function testArchiveBookmarkSuccess(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['success' => true]))
        );
        
        $result = $this->client->archiveBookmark(123);
        
        $this->assertTrue($result);
    }
    
    public function testArchiveBookmarkFailure(): void
    {
        $this->mockHandler->append(
            new Response(400, [], json_encode(['error' => 'Bad request']))
        );
        
        $this->expectException(InstapaperApiException::class);
        $this->expectExceptionMessage('Failed to archive bookmark');
        
        $this->client->archiveBookmark(123);
    }
    
    public function testArchiveBookmarkApiError(): void
    {
        $this->mockHandler->append(
            new Response(500, [], 'Server error')
        );
        
        $this->expectException(InstapaperApiException::class);
        
        $this->client->archiveBookmark(123);
    }
}