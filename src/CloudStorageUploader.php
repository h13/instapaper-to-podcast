<?php

namespace InstapaperToPodcast;

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;

/**
 * Cloud Storage アップローダー
 */
class CloudStorageUploader
{
    private Bucket $bucket;
    private string $bucketName;

    public function __construct(string $bucketName, ?string $keyFilePath = null)
    {
        $config = [];
        if ($keyFilePath !== null) {  // 厳格な比較に変更
            $config['keyFilePath'] = $keyFilePath;
        }

        $storage = new StorageClient($config);
        $this->bucketName = $bucketName;
        $this->bucket = $storage->bucket($bucketName);
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array{name: string, size: int, created: string, publicUrl: string, mediaLink: string}
     */
    public function uploadFile(string $filePath, string $objectName, array $metadata = []): array
    {
        $file = fopen($filePath, 'r');
        if ($file === false) {
            throw new \RuntimeException('Failed to open file: ' . $filePath);
        }

        try {
            $object = $this->bucket->upload($file, [
                'name' => $objectName,
                'metadata' => array_merge([
                    'contentType' => 'audio/mpeg',
                    'cacheControl' => 'public, max-age=3600',
                ], $metadata),
            ]);

            $info = $object->info();

            return [
                'name' => $object->name(),
                'size' => (int) $info['size'],
                'created' => (string) $info['timeCreated'],
                'publicUrl' => sprintf('https://storage.googleapis.com/%s/%s', $this->bucketName, $objectName),
                'mediaLink' => (string) $info['mediaLink'],
            ];

        } finally {
            fclose($file);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $metadata
     * @return array{name: string, publicUrl: string}
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function uploadJson(string $objectName, array $data, array $metadata = []): array
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON');
        }

        $object = $this->bucket->upload($json, [
            'name' => $objectName,
            'metadata' => array_merge([
                'contentType' => 'application/json',
                'cacheControl' => 'public, max-age=300',
            ], $metadata),
        ]);

        return [
            'name' => $object->name(),
            'publicUrl' => sprintf('https://storage.googleapis.com/%s/%s', $this->bucketName, $objectName),
        ];
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function exists(string $objectName): bool
    {
        return $this->bucket->object($objectName)->exists();
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function readJson(string $objectName): ?array
    {
        try {
            $object = $this->bucket->object($objectName);
            if (! $object->exists()) {
                return null;
            }

            $content = $object->downloadAsString();
            $data = json_decode($content, true);

            if (! is_array($data)) {
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            error_log('Failed to read JSON: ' . $e->getMessage());

            return null;
        }
    }
}
