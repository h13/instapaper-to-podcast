<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Exceptions;

/**
 * Exception for storage operations
 */
final class StorageException extends InstapaperToPodcastException
{
    public static function uploadFailed(string $filename, string $reason): self
    {
        return new self("Failed to upload file '{$filename}': {$reason}");
    }

    public static function downloadFailed(string $filename, string $reason): self
    {
        return new self("Failed to download file '{$filename}': {$reason}");
    }

    public static function fileNotFound(string $filename): self
    {
        return new self("File not found: {$filename}");
    }

    public static function invalidBucketName(string $bucketName): self
    {
        return new self("Invalid bucket name: {$bucketName}");
    }
}
