<?php

declare(strict_types=1);

namespace InstapaperToPodcast\ValueObjects;

/**
 * Google Cloud Storage bucket name value object
 */
final class BucketName
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException('Bucket name cannot be empty');
        }

        if (strlen($value) < 3 || strlen($value) > 63) {
            throw new \InvalidArgumentException('Bucket name must be between 3 and 63 characters');
        }

        if (preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $value) !== 1) {
            throw new \InvalidArgumentException('Invalid bucket name format');
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
