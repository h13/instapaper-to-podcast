<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Fake;

use TextSummarizer\Contracts\OpenAiClientInterface;

/**
 * Fake OpenAI Client for testing
 */
final class FakeOpenAiClient implements OpenAiClientInterface
{
    public array $response = [];
    public bool $shouldThrowException = false;
    public ?string $exceptionMessage = null;
    public array $lastRequest = [];

    public function setResponse(array $response): void
    {
        $this->response = $response;
    }

    public function shouldThrowException(bool $should, ?string $message = null): void
    {
        $this->shouldThrowException = $should;
        $this->exceptionMessage = $message;
    }

    public function chat(): object
    {
        $chatApi = new class () {
            public FakeOpenAiClient $client;

            public function create(array $parameters): object
            {
                if ($this->client->shouldThrowException) {
                    throw new \Exception($this->client->exceptionMessage ?? 'OpenAI API error');
                }

                $this->client->lastRequest = $parameters;

                // Create a proper response object structure
                $response = new \stdClass();
                foreach ($this->client->response as $key => $value) {
                    if ($key === 'choices' && is_array($value)) {
                        $response->choices = [];
                        foreach ($value as $choice) {
                            $choiceObj = new \stdClass();
                            if (isset($choice['message'])) {
                                $choiceObj->message = new \stdClass();
                                foreach ($choice['message'] as $msgKey => $msgValue) {
                                    $choiceObj->message->$msgKey = $msgValue;
                                }
                            }
                            $response->choices[] = $choiceObj;
                        }
                    } else {
                        $response->$key = $value;
                    }
                }

                return $response;
            }
        };

        $chatApi->client = $this;
        return $chatApi;
    }

    public function getLastRequest(): array
    {
        return $this->lastRequest;
    }
}
