<?php

namespace Dmytrof\ImportBundle\Tests\Fixtures;

use GuzzleHttp\{Client, Handler\MockHandler, HandlerStack, Middleware};

/**
 * Replaces the reader's Guzzle client with one that serves queued responses.
 * The mock survives cloning (Task::getReader() clones readers from the container).
 */
trait MockHttpClientTrait
{
    private ?MockHandler $mockHandler = null;
    private array $requestHistory = [];

    public function queueResponses(array $responses): static
    {
        $this->mockHandler = new MockHandler($responses);

        return $this;
    }

    public function getRequestHistory(): array
    {
        return $this->requestHistory;
    }

    protected function getClient(): Client
    {
        $stack = HandlerStack::create($this->mockHandler ?? new MockHandler());
        $stack->push(Middleware::history($this->requestHistory));

        return new Client(['handler' => $stack]);
    }
}
