<?php

declare(strict_types=1);

namespace TextSummarizer\Module\Provider;

use OpenAI;
use OpenAI\Client;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use TextSummarizer\Contracts\OpenAiClientInterface;
use TextSummarizer\Contracts\TextSummarizerInterface;
use TextSummarizer\Infrastructure\OpenAiClientAdapter;
use TextSummarizer\Service\TextSummarizer;

final class OpenAiModule extends AbstractModule
{
    public function __construct(
        private array $config
    ) {
    }

    protected function configure(): void
    {
        // OpenAI client
        $this->bind(Client::class)->toProvider(OpenAiClientProvider::class)->in(Scope::SINGLETON);
        
        // OpenAI client interface binding
        $this->bind(OpenAiClientInterface::class)->to(OpenAiClientAdapter::class)->in(Scope::SINGLETON);

        // Text summarizer
        $this->bind(TextSummarizerInterface::class)
            ->to(TextSummarizer::class)
            ->in(Scope::SINGLETON);

        // Configuration
        $this->bind()->annotatedWith('openai.api_key')->toInstance(
            $this->config['OPENAI_API_KEY'] ?? ''
        );
        $this->bind()->annotatedWith('openai.model')->toInstance(
            $this->config['OPENAI_MODEL'] ?? 'gpt-3.5-turbo'
        );
        $this->bind()->annotatedWith('openai.max_tokens')->toInstance(
            (int) ($this->config['OPENAI_MAX_TOKENS'] ?? 500)
        );
    }
}

final class OpenAiClientProvider implements \Ray\Di\ProviderInterface
{
    public function __construct(
        #[\Ray\Di\Di\Named('openai.api_key')]
        private string $apiKey
    ) {
    }

    public function get(): Client
    {
        return OpenAI::client($this->apiKey);
    }
}
