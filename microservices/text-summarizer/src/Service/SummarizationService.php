<?php

declare(strict_types=1);

namespace TextSummarizer\Service;

use Google\Cloud\Storage\StorageClient;
use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;
use TextSummarizer\Contracts\TextSummarizerInterface;

class SummarizationService
{
    #[Inject]
    public function __construct(
        private TextSummarizerInterface $summarizer,
        private StorageClient $storageClient,
        #[Named('storage.bucket')]
        private string $bucketName,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get existing summaries
     */
    public function getSummaries(int $limit): array
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        $summaries = [];

        $objects = $bucket->objects([
            'prefix' => 'summaries/',
            'maxResults' => $limit,
        ]);

        foreach ($objects as $object) {
            /** @var \Google\Cloud\Storage\StorageObject $object */
            if (str_ends_with($object->name(), '.json')) {
                try {
                    $content = $object->downloadAsString();
                    $data = json_decode($content, true);
                    if (is_array($data)) {
                        $summaries[] = [
                            'bookmark_id' => $data['bookmark_id'] ?? null,
                            'title' => $data['title'] ?? '',
                            'summarized_at' => $data['summarized_at'] ?? null,
                            'path' => $object->name(),
                        ];
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to read summary', [
                        'file' => $object->name(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $summaries;
    }

    /**
     * Process unprocessed texts from Cloud Storage
     *
     * @return array{processed: int, failed: int, errors: list<array{file: string, error: string}>}
     */
    public function processTexts(int $limit = 10): array
    {
        $this->logger->info('Starting text summarization', ['limit' => $limit]);

        $processed = 0;
        $failed = 0;
        $errors = [];

        try {
            $bucket = $this->storageClient->bucket($this->bucketName);

            // List unprocessed texts
            $prefix = 'raw-texts/';
            $objects = $bucket->objects([
                'prefix' => $prefix,
                'maxResults' => $limit,
            ]);

            foreach ($objects as $object) {
                /** @var \Google\Cloud\Storage\StorageObject $object */
                $objectName = $object->name();

                // Skip if already summarized
                if ($this->isSummarized($objectName)) {
                    continue;
                }

                try {
                    // Download and parse JSON
                    $content = $object->downloadAsString();
                    $data = json_decode($content, true);

                    if (!is_array($data) || !isset($data['text'])) {
                        throw new \RuntimeException('Invalid bookmark data format');
                    }

                    // Summarize text
                    $summary = $this->summarizer->summarize($data['text']);

                    // Prepare summarized data
                    $summarizedData = array_merge($data, [
                        'summary' => $summary,
                        'summarized_at' => date(\DateTimeInterface::ATOM),
                        'status' => 'summarized',
                    ]);

                    // Store summarized version
                    $this->storeSummary($summarizedData);
                    $processed++;

                    $this->logger->info('Summarized text', [
                        'bookmark_id' => $data['bookmark_id'] ?? 'unknown',
                        'title' => $data['title'] ?? 'unknown',
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'file' => $objectName,
                        'error' => $e->getMessage(),
                    ];

                    $this->logger->error('Failed to summarize text', [
                        'file' => $objectName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process texts', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $this->logger->info('Text summarization completed', [
            'processed' => $processed,
            'failed' => $failed,
        ]);

        return [
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Check if a text has already been summarized
     */
    private function isSummarized(string $objectName): bool
    {
        // Extract bookmark ID from path
        if (!preg_match('/raw-texts\/.*\/(\d+)\.json$/', $objectName, $matches)) {
            return false;
        }

        $bookmarkId = $matches[1];
        $summaryPath = str_replace('raw-texts/', 'summaries/', $objectName);

        $bucket = $this->storageClient->bucket($this->bucketName);
        return $bucket->object($summaryPath)->exists();
    }

    /**
     * Store summarized data in Cloud Storage
     *
     * @param array{bookmark_id: int, title: string, url: string, text: string, summary: string, summarized_at: string, status: string} $data
     */
    private function storeSummary(array $data): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        $objectName = sprintf(
            'summaries/%s/%s.json',
            date('Y/m/d'),
            $data['bookmark_id']
        );

        $bucket->upload(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            [
                'name' => $objectName,
                'metadata' => [
                    'contentType' => 'application/json',
                    'bookmark_id' => (string) $data['bookmark_id'],
                    'title' => $data['title'],
                    'status' => $data['status'],
                ],
            ]
        );
    }
}
