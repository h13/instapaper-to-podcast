<?php

declare(strict_types=1);

namespace TextToSpeech\Tests\Fake;

/**
 * Fake implementation of TextToSpeechService for testing
 */
final class FakeTextToSpeechService
{
    private array $audioFiles = [];
    private array $processResult = ['processed' => 0, 'failed' => 0, 'errors' => []];
    private bool $shouldThrowException = false;
    private string $exceptionMessage = '';

    public function setAudioFiles(array $files): void
    {
        $this->audioFiles = $files;
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

    public function getAudioFiles(int $limit): array
    {
        if ($this->shouldThrowException) {
            throw new \Exception($this->exceptionMessage ?: 'Service error');
        }

        return array_slice($this->audioFiles, 0, $limit);
    }

    public function processSummaries(int $limit = 10): array
    {
        if ($this->shouldThrowException) {
            throw new \Exception($this->exceptionMessage ?: 'Service error');
        }

        return $this->processResult;
    }
}