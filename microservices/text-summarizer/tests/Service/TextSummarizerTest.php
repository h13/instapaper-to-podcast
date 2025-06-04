<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TextSummarizer\Contracts\TextSummarizerInterface;
use TextSummarizer\Exceptions\TextProcessingException;
use TextSummarizer\Service\TextSummarizer;
use TextSummarizer\Tests\Fake\FakeOpenAiClient;

/**
 * @covers \TextSummarizer\Service\TextSummarizer
 */
final class TextSummarizerTest extends TestCase
{
    private FakeOpenAiClient $openAiClient;
    private TextSummarizer $summarizer;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->openAiClient = new FakeOpenAiClient();
        $this->logger = new NullLogger();
        
        $this->summarizer = new TextSummarizer(
            $this->openAiClient,
            'gpt-3.5-turbo',
            500,
            $this->logger
        );
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(TextSummarizerInterface::class, $this->summarizer);
    }

    public function testSummarizeSuccess(): void
    {
        $text = 'This is a long article about artificial intelligence. It discusses machine learning, neural networks, and the future of AI technology. The article explores various applications and ethical considerations.';

        $this->openAiClient->setResponse([
            'choices' => [
                [
                    'message' => [
                        'content' => 'This article explores AI, covering machine learning, neural networks, and future applications while addressing ethical considerations.',
                    ],
                ],
            ],
        ]);

        $summary = $this->summarizer->summarize($text);

        $this->assertNotEmpty($summary);
        $this->assertStringContainsString('AI', $summary);
        $this->assertLessThan(strlen($text), strlen($summary));
    }

    public function testSummarizeEmptyText(): void
    {
        $this->expectException(TextProcessingException::class);
        $this->expectExceptionMessage('Text cannot be empty');

        $this->summarizer->summarize('');
    }

    public function testSummarizeWhitespaceText(): void
    {
        $this->expectException(TextProcessingException::class);
        $this->expectExceptionMessage('Text cannot be empty');

        $this->summarizer->summarize('   ');
    }

    public function testSummarizeApiError(): void
    {
        $this->openAiClient->shouldThrowException(true, 'API rate limit exceeded');

        $this->expectException(TextProcessingException::class);
        $this->expectExceptionMessage('Failed to summarize text: API rate limit exceeded');

        $this->summarizer->summarize('Some text to summarize');
    }

    public function testSummarizeEmptyResponse(): void
    {
        $this->openAiClient->setResponse([
            'choices' => [
                [
                    'message' => [
                        'content' => '',
                    ],
                ],
            ],
        ]);

        $this->expectException(TextProcessingException::class);
        $this->expectExceptionMessage('OpenAI returned empty summary');

        $this->summarizer->summarize('Some text to summarize');
    }

    public function testSummarizeMissingContent(): void
    {
        $this->openAiClient->setResponse([
            'choices' => [
                [
                    'message' => [],
                ],
            ],
        ]);

        $this->expectException(TextProcessingException::class);
        $this->expectExceptionMessage('OpenAI returned empty summary');

        $this->summarizer->summarize('Some text to summarize');
    }

    public function testSummarizeWithSystemPrompt(): void
    {
        $text = 'Technical article about quantum computing';

        $this->openAiClient->setResponse([
            'choices' => [
                [
                    'message' => [
                        'content' => 'A summary of quantum computing concepts suitable for podcast listeners.',
                    ],
                ],
            ],
        ]);

        $summary = $this->summarizer->summarize($text);

        // Verify the request was made with correct parameters
        $lastRequest = $this->openAiClient->getLastRequest();
        $this->assertEquals('gpt-3.5-turbo', $lastRequest['model']);
        $this->assertEquals(500, $lastRequest['max_tokens']);
        $this->assertEquals(0.7, $lastRequest['temperature']);

        // Check system prompt is present
        $messages = $lastRequest['messages'];
        $this->assertCount(2, $messages);
        $this->assertEquals('system', $messages[0]['role']);
        $this->assertStringContainsString('professional content summarizer', $messages[0]['content']);
        $this->assertStringContainsString('podcast format', $messages[0]['content']);
    }
}
