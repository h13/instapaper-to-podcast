<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Module;

use Google\Cloud\Storage\StorageClient;
use Psr\Log\LoggerInterface;
use Ray\Di\AbstractModule;
use TextSummarizer\Contracts\OpenAiClientInterface;
use TextSummarizer\Contracts\TextSummarizerInterface;
use TextSummarizer\Module\LoggerProvider;
use TextSummarizer\Service\SummarizationService;
use TextSummarizer\Service\TextSummarizer;
use TextSummarizer\Tests\Fake\FakeOpenAiClient;
use TextSummarizer\Tests\Fake\FakeStorageClient;

final class TestAppModule extends AbstractModule
{
    protected function configure(): void
    {
        // Bind fake implementations for testing
        $fakeOpenAiClient = new FakeOpenAiClient();
        $this->bind(OpenAiClientInterface::class)->toInstance($fakeOpenAiClient);
        $this->bind(StorageClient::class)->toInstance(new FakeStorageClient());
        $this->bind(TextSummarizerInterface::class)->to(TextSummarizer::class);
        $this->bind(SummarizationService::class);
        $this->bind(LoggerInterface::class)->toProvider(LoggerProvider::class);
        
        // Named bindings
        $this->bind('')->annotatedWith('openai.api_key')->toInstance('sk-test');
        $this->bind('')->annotatedWith('openai.model')->toInstance('gpt-3.5-turbo');
        $this->bind('')->annotatedWith('openai.max_tokens')->toInstance(500);
        $this->bind('')->annotatedWith('storage.bucket')->toInstance('test-bucket');
        $this->bind('')->annotatedWith('storage.bucket_name')->toInstance('test-bucket');
    }
}