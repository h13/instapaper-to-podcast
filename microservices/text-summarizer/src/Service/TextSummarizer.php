<?php

declare(strict_types=1);

namespace TextSummarizer\Service;

use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;
use TextSummarizer\Contracts\OpenAiClientInterface;
use TextSummarizer\Contracts\TextSummarizerInterface;
use TextSummarizer\Exceptions\TextProcessingException;
use TextSummarizer\Infrastructure\Logging\LoggerAwareTrait;

final class TextSummarizer implements TextSummarizerInterface
{
    use LoggerAwareTrait;

    private OpenAiClientInterface $openAiClient;
    private string $model;
    private int $maxTokens;

    #[Inject]
    public function __construct(
        OpenAiClientInterface $openAiClient,
        #[Named('openai.model')]
        string $model,
        #[Named('openai.max_tokens')]
        int $maxTokens,
        LoggerInterface $logger
    ) {
        $this->openAiClient = $openAiClient;
        $this->model = $model;
        $this->maxTokens = $maxTokens;
        $this->logger = $logger;
    }

    /**
     * @throws TextProcessingException
     */
    public function summarize(string $text): string
    {
        $this->logDebug('Starting text summarization', [
            'text_length' => strlen($text),
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
        ]);

        if (trim($text) === '') {
            throw new TextProcessingException('Text cannot be empty');
        }

        try {
            $systemPrompt = <<<EOT
                You are a professional content summarizer. Create a concise, informative summary of the provided text.
                The summary should:
                1. Capture the main ideas and key points
                2. Be written in clear, accessible language
                3. Be suitable for a podcast format (conversational tone)
                4. Be between 150-300 words
                5. Maintain the original context and meaning
                EOT;

            $response = $this->openAiClient->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => "Please summarize the following text:\n\n" . $text,
                    ],
                ],
                'max_tokens' => $this->maxTokens,
                'temperature' => 0.7,
            ]);

            $summary = $response->choices[0]->message->content ?? '';

            if (trim($summary) === '') {
                throw new TextProcessingException('OpenAI returned empty summary');
            }

            $this->logInfo('Text summarized successfully', [
                'original_length' => strlen($text),
                'summary_length' => strlen($summary),
                'model' => $this->model,
            ]);

            return $summary;
        } catch (\Exception $e) {
            $this->logError('Failed to summarize text', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);

            throw new TextProcessingException(
                'Failed to summarize text: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
