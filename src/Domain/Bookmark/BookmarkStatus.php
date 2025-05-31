<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Domain\Bookmark;

/**
 * Bookmark status enumeration
 */
enum BookmarkStatus: string
{
    case UNPROCESSED = 'unprocessed';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
}
