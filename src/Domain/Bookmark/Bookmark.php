<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Domain\Bookmark;

use InstapaperToPodcast\Domain\Shared\AggregateRoot;
use InstapaperToPodcast\ValueObjects\BookmarkId;
use InstapaperToPodcast\ValueObjects\Url;

/**
 * Bookmark aggregate root
 */
final class Bookmark extends AggregateRoot
{
    private BookmarkId $id;
    private string $title;
    private Url $url;
    private ?string $text = null;
    private ?string $summary = null;
    private BookmarkStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $processedAt = null;

    private function __construct(
        BookmarkId $id,
        string $title,
        Url $url,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->url = $url;
        $this->status = BookmarkStatus::UNPROCESSED;
        $this->createdAt = $createdAt;

        $this->raise(new BookmarkCreated($id, $title, $url, $createdAt));
    }

    public static function create(
        BookmarkId $id,
        string $title,
        Url $url,
        \DateTimeImmutable $createdAt
    ): self {
        return new self($id, $title, $url, $createdAt);
    }

    public function fetchText(string $text): void
    {
        if ($this->status !== BookmarkStatus::UNPROCESSED) {
            throw new \DomainException('Cannot fetch text for already processed bookmark');
        }

        $this->text = $text;
        $this->raise(new BookmarkTextFetched($this->id, strlen($text)));
    }

    public function summarize(string $summary): void
    {
        if ($this->text === null) {
            throw new \DomainException('Cannot summarize bookmark without text');
        }

        $this->summary = $summary;
        $this->raise(new BookmarkSummarized($this->id, strlen($summary)));
    }

    public function markAsProcessed(): void
    {
        if ($this->summary === null) {
            throw new \DomainException('Cannot mark bookmark as processed without summary');
        }

        $this->status = BookmarkStatus::PROCESSED;
        $this->processedAt = new \DateTimeImmutable();
        $this->raise(new BookmarkProcessed($this->id, $this->processedAt));
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function markAsFailed(string $reason): void
    {
        $this->status = BookmarkStatus::FAILED;
        $this->raise(new BookmarkProcessingFailed($this->id, $reason));
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getId(): BookmarkId
    {
        return $this->id;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getUrl(): Url
    {
        return $this->url;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getSummary(): ?string
    {
        return $this->summary;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getStatus(): BookmarkStatus
    {
        return $this->status;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    /**
     * Check if the bookmark is processed
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function isProcessed(): bool
    {
        return $this->status === BookmarkStatus::PROCESSED;
    }
}
