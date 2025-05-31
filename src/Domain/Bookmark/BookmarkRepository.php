<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Domain\Bookmark;

use InstapaperToPodcast\ValueObjects\BookmarkId;

/**
 * Repository interface for Bookmark aggregate
 */
interface BookmarkRepository
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function save(Bookmark $bookmark): void;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function exists(BookmarkId $id): bool;
}
