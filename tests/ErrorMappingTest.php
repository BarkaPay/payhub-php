<?php

declare(strict_types=1);

namespace PayHub\Tests;

use PayHub\Client;
use PayHub\Exception\AuthenticationException;
use PayHub\Exception\ConflictException;
use PayHub\Exception\NetworkException;
use PayHub\Exception\RateLimitException;
use PayHub\Exception\ValidationException;
use PayHub\Http\Response;
use PayHub\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\TestCase;

final class ErrorMappingTest extends TestCase
{
    private function clientReturning(Response $response, int $maxRetries = 0): Client
    {
        return new Client(
            apiKey: 'k:s',
            country: 'bf',
            maxRetries: $maxRetries,
            httpClient: new FakeHttpClient([$response]),
        );
    }

    public function testValidationErrorMapsAndExposesFields(): void
    {
        $client = $this->clientReturning(FakeHttpClient::json(422, [
            'status' => 'failed',
            'code' => 'validation_error',
            'message' => 'The given data was invalid.',
            'request_id' => 'rid-1',
            'errors' => ['operator' => ['Unknown operator.']],
        ]));

        try {
            $client->payments->create(['x' => 1]);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame('validation_error', $e->getErrorCode());
            self::assertSame(422, $e->getHttpStatus());
            self::assertSame('rid-1', $e->getRequestId());
            self::assertSame(['operator' => ['Unknown operator.']], $e->getErrors());
        }
    }

    public function testAuthenticationError(): void
    {
        $client = $this->clientReturning(FakeHttpClient::json(401, [
            'status' => 'failed', 'code' => 'invalid_api_key', 'message' => 'bad key',
        ]));

        $this->expectException(AuthenticationException::class);
        $client->me();
    }

    public function testConflictError(): void
    {
        $client = $this->clientReturning(FakeHttpClient::json(409, [
            'status' => 'failed', 'code' => 'duplicate_transaction', 'message' => 'dup',
        ]));

        $this->expectException(ConflictException::class);
        $client->transfers->create(['x' => 1]);
    }

    public function testRateLimitErrorAfterRetriesExhausted(): void
    {
        $client = $this->clientReturning(
            FakeHttpClient::json(429, ['status' => 'error', 'code' => 'too_many_requests', 'message' => 'slow down']),
            0,
        );

        $this->expectException(RateLimitException::class);
        $client->balance->get();
    }

    public function testNetworkErrorBubblesUp(): void
    {
        $client = new Client(
            apiKey: 'k:s',
            country: 'bf',
            maxRetries: 0,
            httpClient: new FakeHttpClient([new NetworkException('connection refused')]),
        );

        $this->expectException(NetworkException::class);
        $client->ping();
    }
}
