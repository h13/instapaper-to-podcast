<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Domain\Bookmark;

use InstapaperToPodcast\Domain\Shared\Event\DomainEvent;
use InstapaperToPodcast\ValueObjects\BookmarkId;

/**
 * Event raised when bookmark is summarized
 */
final class BookmarkSummarized implements DomainEvent
{
    private BookmarkId $bookmarkId;
    private int $summaryLength;
    private \DateTimeImmutable $occurredAt;

    public function __construct(BookmarkId $bookmarkId, int $summaryLength)
    {
        $this->bookmarkId = $bookmarkId;
        $this->summaryLength = $summaryLength;
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getAggregateId(): string
    {
        return $this->bookmarkId->toString();
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getEventName(): string
    {
        return 'bookmark.summarized';
    }

    public function toArray(): array
    {
        return [
            'bookmarkId' => $this->bookmarkId->toString(),
            'summaryLength' => $this->summaryLength,
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
