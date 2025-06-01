<?php

declare(strict_types=1);

namespace InstapaperFetcher\Contracts;

interface InstapaperClientInterface
{
    /**
     * Get bookmarks from Instapaper
     *
     * @param int $limit
     * @param string $folder
     * @return array<array{bookmark_id: int, title: string, url: string, time: int, description: string, hash: string}>
     */
    public function getBookmarks(int $limit = 10, string $folder = 'unread'): array;

    /**
     * Get text content from a bookmark
     *
     * @param int $bookmarkId
     * @return string
     */
    public function getText(int $bookmarkId): string;

    /**
     * Archive a bookmark
     *
     * @param int $bookmarkId
     * @return bool
     */
    public function archiveBookmark(int $bookmarkId): bool;
}