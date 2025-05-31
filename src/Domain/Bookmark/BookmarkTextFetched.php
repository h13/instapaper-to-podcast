<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Domain\Bookmark;

use InstapaperToPodcast\Domain\Shared\Event\DomainEvent;
use InstapaperToPodcast\ValueObjects\BookmarkId;

/**
 * Event raised when bookmark text is fetched
 */
final class BookmarkTextFetched implements DomainEvent
{
    private BookmarkId $bookmarkId;
    private int $textLength;
    private \DateTimeImmutable $occurredAt;

    public function __construct(BookmarkId $bookmarkId, int $textLength)
    {
        $this->bookmarkId = $bookmarkId;
        $this->textLength = $textLength;
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
        return 'bookmark.text_fetched';
    }

    public function toArray(): array
    {
        return [
            'bookmarkId' => $this->bookmarkId->toString(),
            'textLength' => $this->textLength,
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
