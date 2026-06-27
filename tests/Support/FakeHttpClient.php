<?php

declare(strict_types=1);

namespace PayHub\Tests\Support;

use PayHub\Http\HttpClientInterface;
use PayHub\Http\Request;
use PayHub\Http\Response;

/**
 * Records the request and replays queued responses — no network.
 */
final class FakeHttpClient implements HttpClientInterface
{
    public ?Request $lastRequest = null;

    /** @var list<Request> */
    public array $requests = [];

    /** @var list<Response|\Throwable> */
    private array $queue;

    /**
     * @param list<Response|\Throwable> $responses
     */
    public function __construct(array $responses)
    {
        $this->queue = $responses;
    }

    public function send(Request $request): Response
    {
        $this->lastRequest = $request;
        $this->requests[] = $request;

        $next = array_shift($this->queue);
        if ($next === null) {
            throw new \RuntimeException('FakeHttpClient: no more queued responses.');
        }
        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function json(int $status, array $body): Response
    {
        return new Response($status, json_encode($body, JSON_THROW_ON_ERROR));
    }
}
