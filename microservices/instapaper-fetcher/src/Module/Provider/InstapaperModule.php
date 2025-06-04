<?php

declare(strict_types=1);

namespace InstapaperFetcher\Module\Provider;

use InstapaperFetcher\Service\InstapaperClient;
use InstapaperToPodcast\Contracts\InstapaperClientInterface;
use InstapaperToPodcast\Infrastructure\Resilience\CircuitBreaker;
use InstapaperToPodcast\Infrastructure\Resilience\RetryPolicy;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class InstapaperModule extends AbstractModule
{
    public function __construct(
        private array $config
    ) {}

    protected function configure(): void
    {
        // Instapaper client
        $this->bind(InstapaperClientInterface::class)
            ->to(InstapaperClient::class)
            ->in(Scope::SINGLETON);
        
        // Configuration
        $this->bind()->annotatedWith('instapaper.config')->toInstance([
            'consumer_key' => $this->config['INSTAPAPER_CONSUMER_KEY'] ?? '',
            'consumer_secret' => $this->config['INSTAPAPER_CONSUMER_SECRET'] ?? '',
            'access_token' => $this->config['INSTAPAPER_ACCESS_TOKEN'] ?? '',
            'access_token_secret' => $this->config['INSTAPAPER_ACCESS_TOKEN_SECRET'] ?? '',
        ]);
        
        // Circuit breaker
        $this->bind(CircuitBreaker::class)->toProvider(CircuitBreakerProvider::class);
        
        // Retry policy
        $this->bind(RetryPolicy::class)->toProvider(RetryPolicyProvider::class);
    }
}

final class CircuitBreakerProvider implements \Ray\Di\ProviderInterface
{
    public function get(): CircuitBreaker
    {
        return new CircuitBreaker('instapaper-api');
    }
}

final class RetryPolicyProvider implements \Ray\Di\ProviderInterface
{
    public function get(): RetryPolicy
    {
        return RetryPolicy::exponential(3, 100);
    }
}