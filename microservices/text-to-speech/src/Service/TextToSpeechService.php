<?php

declare(strict_types=1);

namespace TextToSpeech\Service;

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use InstapaperToPodcast\Contracts\TextToSpeechInterface;
use InstapaperToPodcast\Exceptions\TextProcessingException;
use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

final class TextToSpeechService
{
    #[Inject]
    public function __construct(
        private TextToSpeechGenerator $ttsGenerator,
        private StorageClient $storageClient,
        #[Named('storage.bucket')]
        private string $bucketName,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get existing audio files
     */
    public function getAudioFiles(int $limit): array
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        $audioFiles = [];

        $objects = $bucket->objects([
            'prefix' => 'audio/',
            'maxResults' => $limit,
        ]);

        foreach ($objects as $object) {
            /** @var \Google\Cloud\Storage\StorageObject $object */
            if (str_ends_with($object->name(), '.mp3')) {
                try {
                    // Extract metadata from object
                    $metadata = $object->info()['metadata'] ?? [];
                    $audioFiles[] = [
                        'bookmark_id' => $metadata['bookmark_id'] ?? null,
                        'title' => $metadata['title'] ?? '',
                        'duration' => $metadata['duration'] ?? null,
                        'created_at' => $metadata['created_at'] ?? null,
                        'path' => $object->name(),
                        'size' => $object->info()['size'] ?? 0,
                    ];
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to read audio file metadata', [
                        'file' => $object->name(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $audioFiles;
    }

    /**
     * Process summarized texts and convert to speech
     *
     * @return array{processed: int, failed: int, errors: list<array{file: string, error: string}>}
     */
    public function processSummaries(int $limit = 10): array
    {
        $this->logger->info('Starting text-to-speech processing', ['limit' => $limit]);

        $processed = 0;
        $failed = 0;
        $errors = [];

        try {
            $bucket = $this->storageClient->bucket($this->bucketName);

            // List summarized texts
            $prefix = 'summaries/';
            $objects = $bucket->objects([
                'prefix' => $prefix,
                'maxResults' => $limit,
            ]);

            foreach ($objects as $object) {
                /** @var \Google\Cloud\Storage\StorageObject $object */
                $objectName = $object->name();

                // Skip if already converted to audio
                if ($this->hasAudio($objectName)) {
                    continue;
                }

                try {
                    // Download and parse JSON
                    $content = $object->downloadAsString();
                    $data = json_decode($content, true);

                    if (!is_array($data) || !isset($data['summary'])) {
                        throw new \RuntimeException('Invalid summary data format');
                    }

                    // Generate audio
                    $audioContent = $this->ttsGenerator->generateSpeech($data['summary']);

                    // Store audio file
                    $this->storeAudio($data, $audioContent);
                    $processed++;

                    $this->logger->info('Generated audio', [
                        'bookmark_id' => $data['bookmark_id'] ?? 'unknown',
                        'title' => $data['title'] ?? 'unknown',
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'file' => $objectName,
                        'error' => $e->getMessage(),
                    ];

                    $this->logger->error('Failed to generate audio', [
                        'file' => $objectName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process summaries', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $this->logger->info('Text-to-speech processing completed', [
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
     * Check if audio already exists for a summary
     */
    private function hasAudio(string $summaryPath): bool
    {
        // Extract bookmark ID from path
        if (!preg_match('/summaries\\/.*\\/(\\d+)\\.json$/', $summaryPath, $matches)) {
            return false;
        }

        $bookmarkId = $matches[1];
        $audioPattern = "audio/*/\\*/{$bookmarkId}.mp3";

        $bucket = $this->storageClient->bucket($this->bucketName);
        $objects = $bucket->objects(['prefix' => 'audio/']);

        foreach ($objects as $object) {
            if (str_ends_with($object->name(), "/{$bookmarkId}.mp3")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store audio file in Cloud Storage
     *
     * @param array{bookmark_id: int, title: string, url: string, summary: string} $data
     */
    private function storeAudio(array $data, string $audioContent): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);

        $objectName = sprintf(
            'audio/%s/%s.mp3',
            date('Y/m/d'),
            $data['bookmark_id']
        );

        // Calculate approximate duration (rough estimate based on content length)
        $wordCount = str_word_count($data['summary']);
        $wordsPerMinute = 150; // Average speaking rate
        $durationMinutes = $wordCount / $wordsPerMinute;
        $durationSeconds = (int) ($durationMinutes * 60);

        $object = $bucket->object($objectName);
        $object->upload($audioContent, [
            'metadata' => [
                'contentType' => 'audio/mpeg',
                'bookmark_id' => (string) $data['bookmark_id'],
                'title' => $data['title'],
                'duration' => (string) $durationSeconds,
                'created_at' => date(\DateTimeInterface::ATOM),
                'status' => 'generated',
            ],
        ]);
    }
}
