<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Contracts;

/**
 * Storage interface for cloud storage operations
 */
interface StorageInterface
{
    /**
     * Upload a file to storage
     * 
     * @param array<string, mixed> $metadata
     * @return array{name: string, size: int, created: string, publicUrl: string, mediaLink: string}
     */
    public function uploadFile(string $filePath, string $objectName, array $metadata = []): array;

    /**
     * Upload JSON data to storage
     * 
     * @param array<string, mixed> $data
     * @param array<string, mixed> $metadata
     * @return array{name: string, publicUrl: string}
     */
    public function uploadJson(string $objectName, array $data, array $metadata = []): array;

    /**
     * Check if an object exists in storage
     */
    public function exists(string $objectName): bool;

    /**
     * Read JSON data from storage
     * 
     * @return array<array-key, mixed>|null
     */
    public function readJson(string $objectName): ?array;
}