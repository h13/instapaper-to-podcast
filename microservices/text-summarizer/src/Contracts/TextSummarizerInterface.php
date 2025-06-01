<?php

declare(strict_types=1);

namespace TextSummarizer\Contracts;

interface TextSummarizerInterface
{
    /**
     * Summarize the given text
     *
     * @param string $text The text to summarize
     *
     * @throws \TextSummarizer\Exceptions\TextProcessingException
     *
     * @return string The summarized text
     */
    public function summarize(string $text): string;
}
