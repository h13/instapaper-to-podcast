<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Contracts;

/**
 * Interface for Instapaper API client
 * 
 * @psalm-import-type Bookmark from \InstapaperToPodcast\ConfigTypes
 */
interface InstapaperClientInterface
{
    /**
     * Get bookmarks from Instapaper
     * 
     * @return list<Bookmark>
     * @throws \InstapaperToPodcast\Exceptions\InstapaperApiException
     */
    public function getBookmarks(int $limit = 10, string $folder = 'unread'): array;

    /**
     * Get article text from Instapaper
     * 
     * @throws \InstapaperToPodcast\Exceptions\InstapaperApiException
     */
    public function getText(int $bookmarkId): string;
}