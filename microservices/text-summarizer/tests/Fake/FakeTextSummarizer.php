<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Fake;

use TextSummarizer\Contracts\TextSummarizerInterface;
use TextSummarizer\Exceptions\TextProcessingException;

/**
 * Fake implementation of TextSummarizer for testing
 * Following the "Fake it, don't mock it" principle
 */
final class FakeTextSummarizer implements TextSummarizerInterface
{
    private bool $shouldThrowException = false;
    private ?string $exceptionMessage = null;
    private array $summaryTemplates = [
        'default' => 'This is a summary of the provided text. The main points are: {{key_points}}. In conclusion, {{conclusion}}.',
        'short' => 'Summary: {{main_point}}.',
        'detailed' => 'This comprehensive summary covers the following aspects: {{intro}}. The key findings include: {{findings}}. The implications are: {{implications}}. To summarize: {{summary}}.',
    ];
    private string $currentTemplate = 'default';
    private int $callCount = 0;

    /**
     * @throws TextProcessingException
     */
    public function summarize(string $text): string
    {
        $this->callCount++;

        if ($this->shouldThrowException) {
            throw new TextProcessingException($this->exceptionMessage ?? 'Summarization failed');
        }

        if (trim($text) === '') {
            throw new TextProcessingException('Text cannot be empty');
        }

        // Simulate rate limiting
        if ($this->callCount > 10) {
            throw new TextProcessingException('Rate limit exceeded');
        }

        // Generate a deterministic summary based on input
        $textLength = strlen($text);
        $wordCount = str_word_count($text);
        $hash = substr(md5($text), 0, 8);

        $template = $this->summaryTemplates[$this->currentTemplate];

        // Replace placeholders with generated content
        $replacements = [
            '{{key_points}}' => "analyzed {$wordCount} words with hash {$hash}",
            '{{conclusion}}' => 'the text provides valuable insights',
            '{{main_point}}' => "Text of {$textLength} characters summarized",
            '{{intro}}' => "an analysis of {$wordCount} words",
            '{{findings}}' => 'significant patterns in the text',
            '{{implications}}' => 'broad applications',
            '{{summary}}' => 'comprehensive analysis complete',
        ];

        $summary = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        // Ensure summary is shorter than original
        if (strlen($summary) >= strlen($text)) {
            $summary = substr($summary, 0, (int)(strlen($text) * 0.3));
        }

        return $summary;
    }

    // Test helper methods

    public function shouldThrowException(bool $should, ?string $message = null): void
    {
        $this->shouldThrowException = $should;
        $this->exceptionMessage = $message;
    }

    public function setTemplate(string $template): void
    {
        if (!isset($this->summaryTemplates[$template])) {
            $this->summaryTemplates[$template] = $template;
        }
        $this->currentTemplate = $template;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }

    public function reset(): void
    {
        $this->shouldThrowException = false;
        $this->exceptionMessage = null;
        $this->currentTemplate = 'default';
        $this->callCount = 0;
    }
}
