<?php

declare(strict_types=1);

namespace TextSummarizer\Resource\App\Summaries;

use BEAR\Resource\ResourceObject;
use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;
use TextSummarizer\Service\SummarizationService;

/**
 * Process texts for summarization
 */
final class Process extends ResourceObject
{
    #[Inject]
    public function __construct(
        private SummarizationService $summarizationService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Process unprocessed texts from Cloud Storage
     */
    public function onPost(int $limit = 10): static
    {
        $this->logger->info('Starting text summarization', ['limit' => $limit]);

        try {
            $result = $this->summarizationService->processTexts($limit);

            $this->code = 201; // Created
            $this->body = [
                'success' => true,
                'result' => $result,
                'timestamp' => date(\DateTimeInterface::ATOM),
                '_links' => [
                    'self' => ['href' => '/summaries/process'],
                    'summaries' => ['href' => '/summaries'],
                ],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to process texts', [
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
