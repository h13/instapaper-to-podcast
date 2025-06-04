<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Module\Provider;

use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use TextSummarizer\Module\Provider\StorageModule;

/**
 * @covers \TextSummarizer\Module\Provider\StorageModule
 * @covers \TextSummarizer\Module\Provider\StorageClientProvider
 */
final class StorageModuleTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $this->config = [
            'STORAGE_BUCKET_NAME' => 'test-bucket',
            'GCP_PROJECT_ID' => 'test-project',
            'GOOGLE_APPLICATION_CREDENTIALS' => '/path/to/credentials.json',
        ];
    }

    public function testModuleConfiguresBindings(): void
    {
        $module = new StorageModule($this->config);
        $injector = new Injector($module);

        // Test named bindings
        $bucketName = $injector->getInstance('', 'storage.bucket');
        $this->assertEquals('test-bucket', $bucketName);

        $projectId = $injector->getInstance('', 'gcp.project_id');
        $this->assertEquals('test-project', $projectId);

        $credentialsPath = $injector->getInstance('', 'gcp.credentials_path');
        $this->assertEquals('/path/to/credentials.json', $credentialsPath);
    }

    public function testModuleWithDefaultValues(): void
    {
        $module = new StorageModule([]);
        $injector = new Injector($module);

        // Test default values
        $bucketName = $injector->getInstance('', 'storage.bucket');
        $this->assertEquals('', $bucketName);

        $projectId = $injector->getInstance('', 'gcp.project_id');
        $this->assertEquals('', $projectId);

        $credentialsPath = $injector->getInstance('', 'gcp.credentials_path');
        $this->assertNull($credentialsPath);
    }
}