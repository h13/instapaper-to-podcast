<?php

declare(strict_types=1);

namespace InstapaperToPodcast\ValueObjects;

/**
 * URL value object
 */
final class Url
{
    private string $value;

    public function __construct(string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Invalid URL format: ' . $value);
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getHost(): string
    {
        $parsed = parse_url($this->value);
        return $parsed['host'] ?? '';
    }

    public function getScheme(): string
    {
        $parsed = parse_url($this->value);
        return $parsed['scheme'] ?? '';
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