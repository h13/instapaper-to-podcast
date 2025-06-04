<?php

declare(strict_types=1);

namespace TextSummarizer\Resource\App;

use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;
use TextSummarizer\Service\SummarizationService;

final class Summaries extends ResourceObject
{
    #[Inject]
    public function __construct(
        private SummarizationService $summarizationService
    ) {
    }

    /**
     * Get summaries list
     */
    public function onGet(int $limit = 10): static
    {
        $summaries = $this->summarizationService->getSummaries($limit);

        $this->body = [
            'summaries' => $summaries,
            'count' => count($summaries),
            '_links' => [
                'self' => ['href' => "/summaries?limit={$limit}"],
                'process' => ['href' => '/summaries/process'],
            ],
        ];

        return $this;
    }
}
