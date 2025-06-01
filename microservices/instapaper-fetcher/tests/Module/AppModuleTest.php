<?php

declare(strict_types=1);

namespace InstapaperFetcher\Tests\Module;

use BEAR\Package\Bootstrap;
use Google\Cloud\Storage\StorageClient;
use InstapaperFetcher\Contracts\InstapaperClientInterface;
use InstapaperFetcher\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ray\Di\Injector;

/**
 * @covers \InstapaperFetcher\Module\AppModule
 */
final class AppModuleTest extends TestCase
{
    public function testModuleBinding(): void
    {
        $module = new TestAppModule();
        $injector = new Injector($module);
        
        // Test Logger binding
        $logger = $injector->getInstance(LoggerInterface::class);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        
        // Test InstapaperClient binding
        $instapaperClient = $injector->getInstance(InstapaperClientInterface::class);
        $this->assertInstanceOf(InstapaperClientInterface::class, $instapaperClient);
        
        // Test Storage binding
        $storageClient = $injector->getInstance(StorageClient::class);
        $this->assertInstanceOf(StorageClient::class, $storageClient);
        
        // Test named binding
        $bucketName = $injector->getInstance('', 'storage.bucket');
        $this->assertEquals('test-bucket', $bucketName);
    }
    
    public function testBootstrapIntegration(): void
    {
        $this->markTestSkipped('Bootstrap requires specific environment setup');
    }
}