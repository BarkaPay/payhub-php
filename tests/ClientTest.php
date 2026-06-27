<?php

declare(strict_types=1);

namespace PayHub\Tests;

use PayHub\Client;
use PayHub\DTO\Payment;
use PayHub\Enum\PaymentStatus;
use PayHub\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testCreatePaymentBuildsSignedCountryScopedRequest(): void
    {
        $http = new FakeHttpClient([
            FakeHttpClient::json(201, [
                'status' => 'success',
                'code' => 'resource_created',
                'data' => ['payment' => [
                    'public_id' => 'pay_1',
                    'status' => 'SUCCESSFUL',
                    'amount' => 10000,
                    'currency' => 'XOF',
                ]],
            ]),
        ]);

        $client = new Client(apiKey: 'pk_live_x:sk_live_y', country: 'bf', maxRetries: 0, httpClient: $http);

        $payment = $client->payments->create([
            'operator' => 'ORANGE',
            'phone_number' => '50123456789',
            'amount' => 10000,
            'order' => ['id' => 'O1'],
        ]);

        self::assertInstanceOf(Payment::class, $payment);
        self::assertSame('pay_1', $payment->publicId);
        self::assertSame(PaymentStatus::Successful, $payment->status);

        $request = $http->lastRequest;
        self::assertNotNull($request);
        self::assertSame('POST', $request->method);
        self::assertSame('https://hub.barkapay.com/bf/v1/payments', $request->url);
        self::assertSame('Bearer pk_live_x:sk_live_y', $request->headers['Authorization']);
        self::assertSame('application/json', $request->headers['Accept']);
        self::assertStringStartsWith('PayHub-PHP/', $request->headers['User-Agent']);
        self::assertArrayHasKey('X-Request-Id', $request->headers);

        self::assertNotNull($request->body);
        $body = json_decode($request->body, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertSame('ORANGE', $body['operator']);
    }

    public function testCountryCanBeOverriddenPerCall(): void
    {
        $http = new FakeHttpClient([FakeHttpClient::json(200, ['status' => 'success', 'data' => []])]);
        $client = new Client(apiKey: 'k:s', country: 'bf', maxRetries: 0, httpClient: $http);

        $client->ping('sn');

        self::assertSame('https://hub.barkapay.com/sn/v1/ping', $http->lastRequest?->url);
    }

    public function testAccidentalBearerPrefixIsForgiven(): void
    {
        $http = new FakeHttpClient([FakeHttpClient::json(200, ['status' => 'success', 'data' => []])]);
        $client = new Client(apiKey: 'Bearer k:s', country: 'bf', maxRetries: 0, httpClient: $http);

        $client->ping();

        self::assertSame('Bearer k:s', $http->lastRequest?->headers['Authorization']);
    }

    public function testListHydratesCollectionPaginationAndQueryString(): void
    {
        $http = new FakeHttpClient([
            FakeHttpClient::json(200, [
                'status' => 'success',
                'data' => ['payments' => [
                    ['public_id' => 'p1', 'status' => 'SUCCESSFUL'],
                    ['public_id' => 'p2', 'status' => 'FAILED'],
                ]],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 2],
            ]),
        ]);
        $client = new Client(apiKey: 'k:s', country: 'bf', maxRetries: 0, httpClient: $http);

        $list = $client->payments->list(['status' => 'SUCCESSFUL']);

        self::assertCount(2, $list);
        self::assertSame(2, $list->meta?->total);

        $ids = [];
        foreach ($list as $payment) {
            $ids[] = $payment->publicId;
        }
        self::assertSame(['p1', 'p2'], $ids);
        self::assertStringContainsString('status=SUCCESSFUL', (string) $http->lastRequest?->url);
    }

    public function testBalanceGet(): void
    {
        $http = new FakeHttpClient([
            FakeHttpClient::json(200, ['status' => 'success', 'data' => [
                'country' => 'BF', 'currency' => 'XOF',
                'available' => '1500.00', 'total' => '1500.00', 'holds' => '0.00',
            ]]),
        ]);
        $client = new Client(apiKey: 'k:s', country: 'bf', maxRetries: 0, httpClient: $http);

        $balance = $client->balance->get();

        self::assertSame('1500.00', $balance->available);
        self::assertSame('XOF', $balance->currency);
        self::assertSame('https://hub.barkapay.com/bf/v1/balance', $http->lastRequest?->url);
    }

    public function testRetriesOn503ThenSucceeds(): void
    {
        $http = new FakeHttpClient([
            FakeHttpClient::json(503, ['status' => 'error', 'code' => 'service_unavailable', 'message' => 'down']),
            FakeHttpClient::json(200, ['status' => 'success', 'data' => []]),
        ]);
        $client = new Client(apiKey: 'k:s', country: 'bf', maxRetries: 1, httpClient: $http);

        $client->ping();

        self::assertCount(2, $http->requests);
    }
}
