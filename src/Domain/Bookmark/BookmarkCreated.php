<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Domain\Bookmark;

use InstapaperToPodcast\Domain\Shared\Event\DomainEvent;
use InstapaperToPodcast\ValueObjects\BookmarkId;
use InstapaperToPodcast\ValueObjects\Url;

/**
 * Event raised when a bookmark is created
 */
final class BookmarkCreated implements DomainEvent
{
    private BookmarkId $bookmarkId;
    private string $title;
    private Url $url;
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        BookmarkId $bookmarkId,
        string $title,
        Url $url,
        \DateTimeImmutable $occurredAt
    ) {
        $this->bookmarkId = $bookmarkId;
        $this->title = $title;
        $this->url = $url;
        $this->occurredAt = $occurredAt;
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
        return 'bookmark.created';
    }

    public function toArray(): array
    {
        return [
            'bookmarkId' => $this->bookmarkId->toString(),
            'title' => $this->title,
            'url' => $this->url->toString(),
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
