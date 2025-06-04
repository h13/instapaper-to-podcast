<?php

declare(strict_types=1);

namespace TextSummarizer\Infrastructure;

use OpenAI\Client;
use TextSummarizer\Contracts\OpenAiClientInterface;

final class OpenAiClientAdapter implements OpenAiClientInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function chat(): object
    {
        return $this->client->chat();
    }
}