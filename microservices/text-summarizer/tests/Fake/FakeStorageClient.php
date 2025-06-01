<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Fake;

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;

/**
 * Fake implementation of Google Cloud Storage for testing
 */
final class FakeStorageClient extends StorageClient
{
    private array $buckets = [];
    private bool $shouldThrowException = false;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config = [])
    {
        // Don't call parent constructor to avoid actual GCP connection
        // $config parameter is for compatibility with parent constructor
        unset($config); // Mark as intentionally unused
    }

    public function bucket($name, $userProject = false, array $options = []): Bucket
    {
        if ($this->shouldThrowException) {
            throw new \Exception('Storage error');
        }

        if (!isset($this->buckets[$name])) {
            $this->buckets[$name] = new FakeBucket($name);
        }

        return $this->buckets[$name];
    }

    public function getBucket(string $name): ?FakeBucket
    {
        return $this->buckets[$name] ?? null;
    }

    public function shouldThrowException(bool $should): void
    {
        $this->shouldThrowException = $should;
    }

    public function reset(): void
    {
        $this->buckets = [];
        $this->shouldThrowException = false;
    }
}

/**
 * Fake implementation of Storage Bucket
 */
final class FakeBucket extends Bucket
{
    private string $name;
    private array $objects = [];

    public function __construct(string $name)
    {
        $this->name = $name;
        // Don't call parent constructor
    }

    public function object($name, array $options = []): StorageObject
    {
        if (!isset($this->objects[$name])) {
            $this->objects[$name] = new FakeStorageObject($name, $this);
        }

        return $this->objects[$name];
    }

    public function objects(array $options = []): \Generator
    {
        $prefix = $options['prefix'] ?? '';
        $maxResults = $options['maxResults'] ?? PHP_INT_MAX;
        $count = 0;

        foreach ($this->objects as $name => $object) {
            if (str_starts_with($name, $prefix) && $count < $maxResults) {
                yield $object;
                $count++;
            }
        }
    }

    public function getObject(string $name): ?FakeStorageObject
    {
        return $this->objects[$name] ?? null;
    }

    public function hasObject(string $name): bool
    {
        return isset($this->objects[$name]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Upload data to the bucket
     *
     * @param string|resource|\Psr\Http\Message\StreamInterface|null $data
     * @param array<string,mixed> $options
     */
    public function upload($data, array $options = []): StorageObject
    {
        $name = $options['name'] ?? throw new \InvalidArgumentException('Name is required');
        $object = $this->object($name);
        $object->upload($data, $options);
        $this->objects[$name] = $object; // Ensure the object is stored
        return $object;
    }
}

/**
 * Fake implementation of Storage Object
 */
final class FakeStorageObject extends StorageObject
{
    private string $name;
    private ?string $content = null;
    private array $metadata = [];
    private bool $exists = false;
    public bool $shouldThrowOnDownload = false;

    /**
     * @param string $name
     * @param FakeBucket $bucket
     */
    public function __construct(string $name, FakeBucket $bucket)
    {
        $this->name = $name;
        // Don't call parent constructor
        unset($bucket); // Mark as intentionally unused (parent compatibility)
    }

    public function upload($data, array $options = []): void
    {
        $this->content = $data;
        $this->metadata = $options['metadata'] ?? [];
        $this->exists = true;
    }

    public function downloadAsString(array $options = []): string
    {
        if ($this->shouldThrowOnDownload) {
            throw new \Exception("Download failed for object {$this->name}");
        }
        
        if (!$this->exists || $this->content === null) {
            throw new \Exception("Object {$this->name} does not exist");
        }

        return $this->content;
    }

    public function exists(array $options = []): bool
    {
        return $this->exists;
    }

    public function info(array $options = []): array
    {
        return [
            'name' => $this->name,
            'size' => strlen($this->content ?? ''),
            'metadata' => $this->metadata,
            'updated' => date(\DateTime::ATOM),
        ];
    }

    public function name(): string
    {
        return $this->name;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
