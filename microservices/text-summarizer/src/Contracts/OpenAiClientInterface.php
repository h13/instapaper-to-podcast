<?php

declare(strict_types=1);

namespace TextSummarizer\Contracts;

interface OpenAiClientInterface
{
    /**
     * Get the chat endpoint object
     */
    public function chat(): object;
}