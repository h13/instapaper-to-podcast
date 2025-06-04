<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Module;

use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\ResourceInterface;
use Google\Cloud\Storage\StorageClient;
use OpenAI\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use TextSummarizer\Contracts\TextSummarizerInterface;
use TextSummarizer\Service\SummarizationService;
use TextSummarizer\Service\TextSummarizer;
use TextSummarizer\Tests\Fake\FakeOpenAiClient;
use TextSummarizer\Tests\Fake\FakeStorageClient;

final class TestResourceModule extends AbstractModule
{
    private StorageClient $storageClient;
    private TextSummarizerInterface $summarizer;
    
    public function __construct(StorageClient $storageClient, TextSummarizerInterface $summarizer)
    {
        $this->storageClient = $storageClient;
        $this->summarizer = $summarizer;
        parent::__construct();
    }
    
    protected function configure(): void
    {
        // Install resource module with namespace
        $this->install(new ResourceModule('TextSummarizer'));
        
        // Bind resource classes explicitly
        $this->bind(\TextSummarizer\Resource\App\Summaries::class);
        $this->bind(\TextSummarizer\Resource\App\Summaries\Process::class);
        
        // Bind fake implementations
        $this->bind(StorageClient::class)->toInstance($this->storageClient);
        $this->bind(TextSummarizerInterface::class)->toInstance($this->summarizer);
        $this->bind(LoggerInterface::class)->toInstance(new NullLogger());
        
        // Bind services
        $this->bind(SummarizationService::class);
        
        // Named bindings
        $this->bind('')->annotatedWith('storage.bucket')->toInstance('test-bucket');
    }
}