<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Domain\Bookmark;

use InstapaperToPodcast\Domain\Shared\Event\DomainEvent;
use InstapaperToPodcast\ValueObjects\BookmarkId;

/**
 * Event raised when bookmark processing fails
 */
final class BookmarkProcessingFailed implements DomainEvent
{
    private BookmarkId $bookmarkId;
    private string $reason;
    private \DateTimeImmutable $occurredAt;

    public function __construct(BookmarkId $bookmarkId, string $reason)
    {
        $this->bookmarkId = $bookmarkId;
        $this->reason = $reason;
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
        return 'bookmark.processing_failed';
    }

    public function toArray(): array
    {
        return [
            'bookmarkId' => $this->bookmarkId->toString(),
            'reason' => $this->reason,
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
