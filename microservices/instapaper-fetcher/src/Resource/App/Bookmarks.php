<?php

declare(strict_types=1);

namespace InstapaperFetcher\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use InstapaperFetcher\Service\BookmarkFetchService;
use Ray\Di\Di\Inject;

final class Bookmarks extends ResourceObject
{
    #[Inject]
    public function __construct(
        private BookmarkFetchService $fetchService
    ) {}
    
    /**
     * Get unprocessed bookmarks
     */
    public function onGet(int $limit = 10): static
    {
        $bookmarks = $this->fetchService->getUnprocessedBookmarks($limit);
        
        $this->body = [
            'bookmarks' => $bookmarks,
            'count' => count($bookmarks),
            '_links' => [
                'self' => ['href' => "/bookmarks?limit={$limit}"],
                'fetch' => ['href' => '/bookmarks/fetch']
            ]
        ];
        
        return $this;
    }
}