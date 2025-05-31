<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Contracts;

/**
 * Interface for text summarization services
 */
interface TextSummarizerInterface
{
    /**
     * Summarize text to a specified maximum length
     *
     * @throws \InstapaperToPodcast\Exceptions\TextProcessingException
     */
    public function summarize(string $text, int $maxLength = 500): string;
}
