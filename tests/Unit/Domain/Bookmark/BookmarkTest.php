<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Bookmark;

use InstapaperToPodcast\Domain\Bookmark\Bookmark;
use InstapaperToPodcast\Domain\Bookmark\BookmarkCreated;
use InstapaperToPodcast\Domain\Bookmark\BookmarkProcessed;
use InstapaperToPodcast\Domain\Bookmark\BookmarkStatus;
use InstapaperToPodcast\Domain\Bookmark\BookmarkSummarized;
use InstapaperToPodcast\Domain\Bookmark\BookmarkTextFetched;
use InstapaperToPodcast\ValueObjects\BookmarkId;
use InstapaperToPodcast\ValueObjects\Url;
use PHPUnit\Framework\TestCase;

final class BookmarkTest extends TestCase
{
    private BookmarkId $bookmarkId;
    private string $title;
    private Url $url;
    private \DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        $this->bookmarkId = new BookmarkId(123);
        $this->title = 'Test Article';
        $this->url = new Url('https://example.com/article');
        $this->createdAt = new \DateTimeImmutable();
    }

    public function testCreateBookmark(): void
    {
        $bookmark = Bookmark::create(
            $this->bookmarkId,
            $this->title,
            $this->url,
            $this->createdAt
        );

        $this->assertEquals($this->bookmarkId, $bookmark->getId());
        $this->assertEquals($this->title, $bookmark->getTitle());
        $this->assertEquals($this->url, $bookmark->getUrl());
        $this->assertEquals(BookmarkStatus::UNPROCESSED, $bookmark->getStatus());
        $this->assertFalse($bookmark->isProcessed());
        $this->assertNull($bookmark->getText());
        $this->assertNull($bookmark->getSummary());
        $this->assertNull($bookmark->getProcessedAt());

        $events = $bookmark->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(BookmarkCreated::class, $events[0]);
    }

    public function testFetchText(): void
    {
        $bookmark = $this->createBookmark();
        $text = 'This is the article content.';

        $bookmark->fetchText($text);

        $this->assertEquals($text, $bookmark->getText());

        $events = $bookmark->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(BookmarkTextFetched::class, $events[0]);
    }

    public function testCannotFetchTextForProcessedBookmark(): void
    {
        $bookmark = $this->createProcessedBookmark();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot fetch text for already processed bookmark');

        $bookmark->fetchText('Some text');
    }

    public function testSummarize(): void
    {
        $bookmark = $this->createBookmark();
        $bookmark->fetchText('Long article text...');
        $bookmark->pullDomainEvents(); // Clear text fetched event

        $summary = 'Short summary of the article.';
        $bookmark->summarize($summary);

        $this->assertEquals($summary, $bookmark->getSummary());

        $events = $bookmark->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(BookmarkSummarized::class, $events[0]);
    }

    public function testCannotSummarizeWithoutText(): void
    {
        $bookmark = $this->createBookmark();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot summarize bookmark without text');

        $bookmark->summarize('Summary');
    }

    public function testMarkAsProcessed(): void
    {
        $bookmark = $this->createBookmark();
        $bookmark->fetchText('Text');
        $bookmark->summarize('Summary');
        $bookmark->pullDomainEvents(); // Clear previous events

        $bookmark->markAsProcessed();

        $this->assertEquals(BookmarkStatus::PROCESSED, $bookmark->getStatus());
        $this->assertTrue($bookmark->isProcessed());
        $this->assertNotNull($bookmark->getProcessedAt());

        $events = $bookmark->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(BookmarkProcessed::class, $events[0]);
    }

    public function testCannotMarkAsProcessedWithoutSummary(): void
    {
        $bookmark = $this->createBookmark();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot mark bookmark as processed without summary');

        $bookmark->markAsProcessed();
    }

    public function testMarkAsFailed(): void
    {
        $bookmark = $this->createBookmark();
        $reason = 'Failed to fetch text';

        $bookmark->markAsFailed($reason);

        $this->assertEquals(BookmarkStatus::FAILED, $bookmark->getStatus());
        $this->assertFalse($bookmark->isProcessed());
    }

    private function createBookmark(): Bookmark
    {
        $bookmark = Bookmark::create(
            $this->bookmarkId,
            $this->title,
            $this->url,
            $this->createdAt
        );

        // Clear creation event
        $bookmark->pullDomainEvents();

        return $bookmark;
    }

    private function createProcessedBookmark(): Bookmark
    {
        $bookmark = $this->createBookmark();
        $bookmark->fetchText('Text');
        $bookmark->summarize('Summary');
        $bookmark->markAsProcessed();

        // Clear all events
        $bookmark->pullDomainEvents();

        return $bookmark;
    }
}
