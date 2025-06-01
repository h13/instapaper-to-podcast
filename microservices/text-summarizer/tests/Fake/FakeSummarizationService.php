<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Fake;

use TextSummarizer\Service\SummarizationService;

/**
 * Fake implementation of SummarizationService for testing
 */
final class FakeSummarizationService extends SummarizationService
{
    private array $summaries = [];
    private array $processResult = ['processed' => 0, 'failed' => 0, 'errors' => []];
    private bool $shouldThrowException = false;
    private string $exceptionMessage = '';

    public function __construct()
    {
        // Skip parent constructor
    }

    public function setSummaries(array $summaries): void
    {
        $this->summaries = $summaries;
    }

    public function setProcessResult(array $result): void
    {
        $this->processResult = $result;
    }

    public function shouldThrowException(bool $should, string $message = ''): void
    {
        $this->shouldThrowException = $should;
        $this->exceptionMessage = $message;
    }

    public function getSummaries(int $limit = 10): array
    {
        if ($this->shouldThrowException) {
            throw new \Exception($this->exceptionMessage ?: 'Service error');
        }

        return array_slice($this->summaries, 0, $limit);
    }

    public function processTexts(int $limit = 10): array
    {
        if ($this->shouldThrowException) {
            throw new \Exception($this->exceptionMessage ?: 'Service error');
        }

        return $this->processResult;
    }
}