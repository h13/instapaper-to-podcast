<?php

declare(strict_types=1);

namespace PodcastPublisher\Module\Provider;

use Google\Cloud\Storage\StorageClient;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class StorageModule extends AbstractModule
{
    public function __construct(
        private array $config
    ) {}

    protected function configure(): void
    {
        // Storage client
        $this->bind(StorageClient::class)->toProvider(StorageClientProvider::class)->in(Scope::SINGLETON);
        
        // Bucket names
        $this->bind()->annotatedWith('storage.bucket')->toInstance(
            $this->config['STORAGE_BUCKET_NAME'] ?? ''
        );
        $this->bind()->annotatedWith('podcast.bucket')->toInstance(
            $this->config['PODCAST_BUCKET_NAME'] ?? ''
        );
        
        // GCP config
        $this->bind()->annotatedWith('gcp.project_id')->toInstance(
            $this->config['GCP_PROJECT_ID'] ?? ''
        );
        $this->bind()->annotatedWith('gcp.credentials_path')->toInstance(
            $this->config['GOOGLE_APPLICATION_CREDENTIALS'] ?? null
        );
    }
}

final class StorageClientProvider implements \Ray\Di\ProviderInterface
{
    public function __construct(
        #[\Ray\Di\Di\Named('gcp.project_id')]
        private string $projectId,
        #[\Ray\Di\Di\Named('gcp.credentials_path')]
        private ?string $credentialsPath = null
    ) {}
    
    public function get(): StorageClient
    {
        $config = ['projectId' => $this->projectId];
        if ($this->credentialsPath) {
            $config['keyFilePath'] = $this->credentialsPath;
        }
        
        return new StorageClient($config);
    }
}