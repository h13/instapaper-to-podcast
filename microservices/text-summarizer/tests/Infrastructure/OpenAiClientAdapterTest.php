<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Infrastructure;

use OpenAI\Client;
use PHPUnit\Framework\TestCase;
use TextSummarizer\Infrastructure\OpenAiClientAdapter;

/**
 * @covers \TextSummarizer\Infrastructure\OpenAiClientAdapter
 */
final class OpenAiClientAdapterTest extends TestCase
{
    public function testAdapterPassesThroughToClient(): void
    {
        $this->markTestSkipped('OpenAI\Client is a final class and cannot be mocked');
    }
}