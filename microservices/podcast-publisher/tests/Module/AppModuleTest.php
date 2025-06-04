<?php

declare(strict_types=1);

namespace PodcastPublisher\Tests\Module;

use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Application\AppInterface;
use Google\Cloud\Storage\StorageClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ray\Di\Injector;
use PodcastPublisher\Module\AppModule;
use PodcastPublisher\Service\PodcastService;
use PodcastPublisher\Service\PodcastFeedGenerator;
use PodcastPublisher\Contracts\PodcastFeedGeneratorInterface;
use PodcastPublisher\Resource\App\Feed;
use PodcastPublisher\Resource\App\Feed\Generate;

/**
 * @covers \PodcastPublisher\Module\AppModule
 * @covers \PodcastPublisher\Module\LoggerProvider
 * @covers \PodcastPublisher\Service\PodcastService
 */
final class AppModuleTest extends TestCase
{
    private Injector $injector;
    
    protected function setUp(): void
    {
        // Set required environment variables
        $_ENV['APP_ENV'] = 'test';
        $_ENV['APP_DEBUG'] = true;
        $_ENV['STORAGE_BUCKET_NAME'] = 'test-bucket';
        $_ENV['PODCAST_BUCKET_NAME'] = 'podcast-bucket';
        $_ENV['GCP_PROJECT_ID'] = 'test-project';
        $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = '/dev/null';
        $_ENV['PODCAST_TITLE'] = 'Test Podcast';
        $_ENV['PODCAST_DESCRIPTION'] = 'Test Description';
        $_ENV['PODCAST_AUTHOR'] = 'Test Author';
        $_ENV['PODCAST_EMAIL'] = 'test@example.com';
        $_ENV['PODCAST_CATEGORY'] = 'Technology';
        $_ENV['PODCAST_LANGUAGE'] = 'en-US';
        $_ENV['PODCAST_IMAGE_URL'] = 'https://example.com/image.jpg';
        $_ENV['PODCAST_WEBSITE_URL'] = 'https://example.com';
        
        $this->injector = new Injector(new TestAppModule());
    }
    
    protected function tearDown(): void
    {
        // Clean up environment
        unset($_ENV['APP_ENV']);
        unset($_ENV['APP_DEBUG']);
        unset($_ENV['STORAGE_BUCKET_NAME']);
        unset($_ENV['PODCAST_BUCKET_NAME']);
        unset($_ENV['GCP_PROJECT_ID']);
        unset($_ENV['GOOGLE_APPLICATION_CREDENTIALS']);
        unset($_ENV['PODCAST_TITLE']);
        unset($_ENV['PODCAST_DESCRIPTION']);
        unset($_ENV['PODCAST_AUTHOR']);
        unset($_ENV['PODCAST_EMAIL']);
        unset($_ENV['PODCAST_CATEGORY']);
        unset($_ENV['PODCAST_LANGUAGE']);
        unset($_ENV['PODCAST_IMAGE_URL']);
        unset($_ENV['PODCAST_WEBSITE_URL']);
    }
    
    public function testLoggerBinding(): void
    {
        $logger = $this->injector->getInstance(LoggerInterface::class);
        
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }
    
    public function testStorageClientBinding(): void
    {
        $client = $this->injector->getInstance(StorageClient::class);
        
        $this->assertInstanceOf(StorageClient::class, $client);
    }
    
    public function testPodcastFeedGeneratorBinding(): void
    {
        $generator = $this->injector->getInstance(PodcastFeedGeneratorInterface::class);
        
        $this->assertInstanceOf(PodcastFeedGeneratorInterface::class, $generator);
        // Generator will be FakePodcastFeedGenerator in tests
    }
    
    public function testPodcastServiceBinding(): void
    {
        $service = $this->injector->getInstance(PodcastService::class);
        
        $this->assertInstanceOf(PodcastService::class, $service);
    }
    
    public function testResourceInterface(): void
    {
        $this->markTestSkipped('ResourceInterface requires BEAR modules');
    }
    
    public function testAppInterface(): void
    {
        $this->markTestSkipped('AppInterface requires BEAR modules');
    }
    
    public function testResourceInjection(): void
    {
        $this->markTestSkipped('Resource injection test requires full BEAR setup');
    }
    
    public function testNamedBindings(): void
    {
        // Test named bindings are configured
        $bucketName = $this->injector->getInstance('', 'storage.bucket');
        $this->assertEquals('test-bucket', $bucketName);
        
        $podcastBucketName = $this->injector->getInstance('', 'podcast.bucket');
        $this->assertEquals('podcast-bucket', $podcastBucketName);
        
        // Podcast configuration
        $title = $this->injector->getInstance('', 'podcast.title');
        $this->assertEquals('Test Podcast', $title);
        
        $description = $this->injector->getInstance('', 'podcast.description');
        $this->assertEquals('Test Description', $description);
        
        $author = $this->injector->getInstance('', 'podcast.author');
        $this->assertEquals('Test Author', $author);
        
        $email = $this->injector->getInstance('', 'podcast.email');
        $this->assertEquals('test@example.com', $email);
        
        $category = $this->injector->getInstance('', 'podcast.category');
        $this->assertEquals('Technology', $category);
        
        $language = $this->injector->getInstance('', 'podcast.language');
        $this->assertEquals('en-US', $language);
        
        $imageUrl = $this->injector->getInstance('', 'podcast.image_url');
        $this->assertEquals('https://example.com/image.jpg', $imageUrl);
        
        $websiteUrl = $this->injector->getInstance('', 'podcast.website_url');
        $this->assertEquals('https://example.com', $websiteUrl);
    }
    
    public function testLoggerProviderCreatesCorrectLogger(): void
    {
        $this->markTestSkipped('LoggerProvider requires LoggerFactory implementation');
    }
    
    public function testModuleInstallsRequiredModules(): void
    {
        $this->markTestSkipped('Module installation test requires full BEAR setup');
    }
}