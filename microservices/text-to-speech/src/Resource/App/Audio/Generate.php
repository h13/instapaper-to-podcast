<?php

declare(strict_types=1);

namespace TextToSpeech\Resource\App\Audio;

use BEAR\Resource\ResourceObject;
use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;
use TextToSpeech\Service\TextToSpeechService;

/**
 * Generate audio from summarized texts
 */
final class Generate extends ResourceObject
{
    #[Inject]
    public function __construct(
        private TextToSpeechService $ttsService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Process summarized texts and generate audio
     */
    public function onPost(int $limit = 10): static
    {
        $this->logger->info('Starting audio generation', ['limit' => $limit]);

        try {
            $result = $this->ttsService->processSummaries($limit);

            $this->code = 201; // Created
            $this->body = [
                'success' => true,
                'result' => $result,
                'timestamp' => date(\DateTimeInterface::ATOM),
                '_links' => [
                    'self' => ['href' => '/audio/generate'],
                    'audio' => ['href' => '/audio'],
                ],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate audio', [
                'error' => $e->getMessage(),
            ]);

            $this->code = 500;
            $this->body = [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date(\DateTimeInterface::ATOM),
            ];
        }

        return $this;
    }
}
