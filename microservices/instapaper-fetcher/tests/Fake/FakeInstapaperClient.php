<?php

declare(strict_types=1);

namespace InstapaperFetcher\Tests\Fake;

use InstapaperFetcher\Contracts\InstapaperClientInterface;
use InstapaperFetcher\Exceptions\InstapaperApiException;

/**
 * Fake implementation of InstapaperClient for testing
 * Following the "Fake it, don't mock it" principle
 */
final class FakeInstapaperClient implements InstapaperClientInterface
{
    /**
     * @var array<array{bookmark_id: int, title: string, url: string, time: int, description: string, hash: string}>
     */
    private array $bookmarks = [];
    
    /**
     * @var array<int, string>
     */
    private array $texts = [];
    
    /**
     * @var array<int>
     */
    private array $archivedBookmarks = [];
    
    private bool $shouldThrowException = false;
    private ?string $exceptionMessage = null;

    public function __construct()
    {
        // Initialize with some test data
        $this->bookmarks = [
            [
                'bookmark_id' => 1,
                'title' => 'Test Article 1',
                'url' => 'https://example.com/article1',
                'time' => time() - 3600,
                'description' => 'Test description 1',
                'hash' => 'hash1'
            ],
            [
                'bookmark_id' => 2,
                'title' => 'Test Article 2',
                'url' => 'https://example.com/article2',
                'time' => time() - 7200,
                'description' => 'Test description 2',
                'hash' => 'hash2'
            ],
            [
                'bookmark_id' => 3,
                'title' => 'Test Article 3',
                'url' => 'https://example.com/article3',
                'time' => time() - 10800,
                'description' => 'Test description 3',
                'hash' => 'hash3'
            ]
        ];
        
        $this->texts = [
            1 => 'This is the full text content of article 1. It contains interesting information.',
            2 => 'This is the full text content of article 2. It has different content.',
            3 => 'This is the full text content of article 3. It is also interesting.'
        ];
    }

    public function getBookmarks(int $limit = 10, string $folder = 'unread'): array
    {
        if ($this->shouldThrowException) {
            throw new InstapaperApiException($this->exceptionMessage ?? 'API Error');
        }
        
        return array_slice($this->bookmarks, 0, $limit);
    }

    public function getText(int $bookmarkId): string
    {
        if ($this->shouldThrowException) {
            throw new InstapaperApiException($this->exceptionMessage ?? 'API Error');
        }
        
        if (!isset($this->texts[$bookmarkId])) {
            throw new InstapaperApiException("Bookmark {$bookmarkId} not found");
        }
        
        return $this->texts[$bookmarkId];
    }

    public function archiveBookmark(int $bookmarkId): bool
    {
        if ($this->shouldThrowException) {
            throw new InstapaperApiException($this->exceptionMessage ?? 'API Error');
        }
        
        if (!isset($this->texts[$bookmarkId])) {
            return false;
        }
        
        $this->archivedBookmarks[] = $bookmarkId;
        return true;
    }
    
    // Test helper methods
    
    public function setBookmarks(array $bookmarks): void
    {
        $this->bookmarks = $bookmarks;
    }
    
    public function setText(int $bookmarkId, string $text): void
    {
        $this->texts[$bookmarkId] = $text;
    }
    
    public function shouldThrowException(bool $should, ?string $message = null): void
    {
        $this->shouldThrowException = $should;
        $this->exceptionMessage = $message;
    }
    
    public function getArchivedBookmarks(): array
    {
        return $this->archivedBookmarks;
    }
    
    public function reset(): void
    {
        $this->__construct();
        $this->shouldThrowException = false;
        $this->exceptionMessage = null;
        $this->archivedBookmarks = [];
    }
}