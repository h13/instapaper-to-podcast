<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Exceptions;

/**
 * Exception for text processing errors
 */
final class TextProcessingException extends InstapaperToPodcastException
{
    public static function summarizationFailed(string $reason): self
    {
        return new self("Text summarization failed: {$reason}");
    }

    public static function speechGenerationFailed(string $reason): self
    {
        return new self("Speech generation failed: {$reason}");
    }

    public static function emptyText(): self
    {
        return new self("Cannot process empty text");
    }

    public static function textTooLong(int $length, int $maxLength): self
    {
        return new self("Text too long: {$length} characters (max: {$maxLength})");
    }
}