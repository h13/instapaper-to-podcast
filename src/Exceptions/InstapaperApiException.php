<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Exceptions;

/**
 * Exception for Instapaper API errors
 */
final class InstapaperApiException extends InstapaperToPodcastException
{
    private ?int $httpCode;

    public function __construct(string $message, ?int $httpCode = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->httpCode = $httpCode;
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public static function fromRequestFailure(string $message, ?int $httpCode = null): self
    {
        return new self("Instapaper API request failed: {$message}", $httpCode);
    }

    public static function invalidResponse(string $reason): self
    {
        return new self("Invalid response from Instapaper API: {$reason}");
    }
}
