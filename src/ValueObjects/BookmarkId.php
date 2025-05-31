<?php

declare(strict_types=1);

namespace InstapaperToPodcast\ValueObjects;

/**
 * Bookmark ID value object
 */
final class BookmarkId
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('Bookmark ID must be positive');
        }

        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function toString(): string
    {
        return (string) $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}