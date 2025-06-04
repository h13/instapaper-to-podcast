<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Module\Provider;

use OpenAI\Client;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use TextSummarizer\Contracts\OpenAiClientInterface;
use TextSummarizer\Contracts\TextSummarizerInterface;
use TextSummarizer\Module\Provider\OpenAiModule;
use TextSummarizer\Service\TextSummarizer;

/**
 * @covers \TextSummarizer\Module\Provider\OpenAiModule
 * @covers \TextSummarizer\Module\Provider\OpenAiClientProvider
 */
final class OpenAiModuleTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $this->config = [
            'OPENAI_API_KEY' => 'sk-test123',
            'OPENAI_MODEL' => 'gpt-3.5-turbo',
            'OPENAI_MAX_TOKENS' => '500',
        ];
    }

    public function testModuleConfiguresBindings(): void
    {
        $module = new OpenAiModule($this->config);
        $injector = new Injector($module);

        // Test named bindings
        $apiKey = $injector->getInstance('', 'openai.api_key');
        $this->assertEquals('sk-test123', $apiKey);

        $model = $injector->getInstance('', 'openai.model');
        $this->assertEquals('gpt-3.5-turbo', $model);

        $maxTokens = $injector->getInstance('', 'openai.max_tokens');
        $this->assertEquals(500, $maxTokens);
    }

    public function testModuleWithDefaultValues(): void
    {
        $module = new OpenAiModule([]);
        $injector = new Injector($module);

        // Test default values
        $apiKey = $injector->getInstance('', 'openai.api_key');
        $this->assertEquals('', $apiKey);

        $model = $injector->getInstance('', 'openai.model');
        $this->assertEquals('gpt-3.5-turbo', $model);

        $maxTokens = $injector->getInstance('', 'openai.max_tokens');
        $this->assertEquals(500, $maxTokens);
    }
}