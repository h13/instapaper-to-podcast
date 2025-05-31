<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Domain\Bookmark;

use InstapaperToPodcast\Domain\Shared\Event\DomainEvent;
use InstapaperToPodcast\ValueObjects\BookmarkId;

/**
 * Event raised when bookmark processing is completed
 */
final class BookmarkProcessed implements DomainEvent
{
    private BookmarkId $bookmarkId;
    private \DateTimeImmutable $processedAt;
    private \DateTimeImmutable $occurredAt;

    public function __construct(BookmarkId $bookmarkId, \DateTimeImmutable $processedAt)
    {
        $this->bookmarkId = $bookmarkId;
        $this->processedAt = $processedAt;
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
        return 'bookmark.processed';
    }

    public function toArray(): array
    {
        return [
            'bookmarkId' => $this->bookmarkId->toString(),
            'processedAt' => $this->processedAt->format(\DateTimeInterface::ATOM),
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
