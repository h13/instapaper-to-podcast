<?php

declare(strict_types=1);

namespace InstapaperFetcher\Service;

use Google\Cloud\Storage\StorageClient;
use InstapaperFetcher\Contracts\InstapaperClientInterface;
use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

final class BookmarkFetchService
{
    #[Inject]
    public function __construct(
        private InstapaperClientInterface $instapaperClient,
        private StorageClient $storageClient,
        #[Named('storage.bucket')]
        private string $bucketName,
        private LoggerInterface $logger
    ) {}

    /**
     * Get unprocessed bookmarks
     */
    public function getUnprocessedBookmarks(int $limit): array
    {
        // This would typically check against a database or storage
        // For now, returns empty array
        return [];
    }

    /**
     * Fetch bookmarks from Instapaper and store them in Cloud Storage
     *
     * @return array{fetched: int, failed: int, errors: list<array{id: int, error: string}>}
     */
    public function fetchAndStore(int $limit = 10): array
    {
        $this->logger->info('Starting bookmark fetch', ['limit' => $limit]);

        $fetched = 0;
        $failed = 0;
        $errors = [];

        try {
            // Fetch bookmarks
            $bookmarks = $this->instapaperClient->getBookmarks($limit);

            $this->logger->info('Fetched bookmarks', ['count' => count($bookmarks)]);

            foreach ($bookmarks as $bookmark) {
                try {
                    // Fetch full text
                    $text = $this->instapaperClient->getText($bookmark['bookmark_id']);
                    
                    // Prepare data for storage
                    $data = [
                        'bookmark_id' => $bookmark['bookmark_id'],
                        'title' => $bookmark['title'],
                        'url' => $bookmark['url'],
                        'text' => $text,
                        'fetched_at' => date(\DateTimeInterface::ATOM),
                        'status' => 'fetched'
                    ];

                    // Store in Cloud Storage
                    $this->storeBookmark($data);
                    $fetched++;

                    $this->logger->info('Stored bookmark', [
                        'bookmark_id' => $bookmark['bookmark_id'],
                        'title' => $bookmark['title']
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'id' => $bookmark['bookmark_id'],
                        'error' => $e->getMessage(),
                    ];

                    $this->logger->error('Failed to process bookmark', [
                        'bookmark_id' => $bookmark['bookmark_id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch bookmarks', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $this->logger->info('Bookmark fetch completed', [
            'fetched' => $fetched,
            'failed' => $failed,
        ]);

        return [
            'fetched' => $fetched,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Store bookmark data in Cloud Storage
     *
     * @param array{bookmark_id: int, title: string, url: string, text: string, fetched_at: string, status: string} $data
     */
    private function storeBookmark(array $data): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        
        $objectName = sprintf(
            'raw-texts/%s/%s.json',
            date('Y/m/d'),
            $data['bookmark_id']
        );

        $object = $bucket->object($objectName);
        $object->upload(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            [
                'metadata' => [
                    'contentType' => 'application/json',
                    'bookmark_id' => (string) $data['bookmark_id'],
                    'title' => $data['title'],
                    'status' => $data['status']
                ]
            ]
        );
    }
}