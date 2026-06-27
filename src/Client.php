<?php

declare(strict_types=1);

namespace PayHub;

use PayHub\Exception\ConfigurationException;
use PayHub\Http\CurlClient;
use PayHub\Http\HttpClientInterface;
use PayHub\Resources\Balance;
use PayHub\Resources\Operators;
use PayHub\Resources\Payments;
use PayHub\Resources\Transfers;
use PayHub\Util\Coerce;

/**
 * Entry point to the PayHub API.
 *
 * ```php
 * $payhub = new \PayHub\Client(apiKey: 'pk_live_xxx:sk_live_yyy', country: 'bf');
 * $payment = $payhub->payments->create([
 *     'operator'     => 'ORANGE',
 *     'phone_number' => '50123456789',
 *     'amount'       => 10000,
 *     'otp'          => '123456',
 *     'order'        => ['id' => 'ORDER-2026-001'],
 * ]);
 * ```
 */
final class Client
{
    public const VERSION = '0.1.0';

    public readonly Payments $payments;
    public readonly Transfers $transfers;
    public readonly Balance $balance;
    public readonly Operators $operators;

    private readonly ApiClient $api;

    /**
     * @param string|null $apiKey     the merchant key as "key_id:secret" (what you copy from the dashboard).
     *                                Do NOT prefix it with "Bearer" — the SDK does that.
     * @param string|null $keyId      alternatively, the key id…
     * @param string|null $secret     …and the secret, passed separately
     * @param string      $country    default country code (e.g. "bf"); overridable per call
     * @param string      $baseUrl    API base URL
     * @param int         $maxRetries automatic retries on 429/503/network errors
     * @param float       $timeout    per-request timeout in seconds
     */
    public function __construct(
        ?string $apiKey = null,
        ?string $keyId = null,
        ?string $secret = null,
        string $country = '',
        string $baseUrl = 'https://hub.barkapay.com',
        int $maxRetries = 2,
        float $timeout = 30.0,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->api = new ApiClient(
            httpClient: $httpClient ?? new CurlClient($timeout),
            authorization: self::buildAuthorization($apiKey, $keyId, $secret),
            baseUrl: rtrim($baseUrl, '/'),
            defaultCountry: strtolower(trim($country)),
            maxRetries: max(0, $maxRetries),
            userAgent: 'PayHub-PHP/' . self::VERSION,
        );

        $this->payments = new Payments($this->api);
        $this->transfers = new Transfers($this->api);
        $this->balance = new Balance($this->api);
        $this->operators = new Operators($this->api);
    }

    /**
     * Liveness check (also confirms your key works).
     *
     * @return array<string, mixed>
     */
    public function ping(?string $country = null): array
    {
        return Coerce::arr($this->api->request('GET', 'ping', $country), 'data') ?? [];
    }

    /**
     * Current merchant + API application details.
     *
     * @return array<string, mixed>
     */
    public function me(?string $country = null): array
    {
        return Coerce::arr($this->api->request('GET', 'me', $country), 'data') ?? [];
    }

    private static function buildAuthorization(?string $apiKey, ?string $keyId, ?string $secret): string
    {
        if ($apiKey !== null && trim($apiKey) !== '') {
            $token = trim($apiKey);
            // Forgive an accidental "Bearer " prefix so we never send "Bearer Bearer …".
            if (stripos($token, 'bearer ') === 0) {
                $token = trim(substr($token, 7));
            }

            return 'Bearer ' . $token;
        }

        if ($keyId !== null && $secret !== null && trim($keyId) !== '' && trim($secret) !== '') {
            return 'Bearer ' . trim($keyId) . ':' . trim($secret);
        }

        throw new ConfigurationException('Provide `apiKey` ("key_id:secret") or both `keyId` and `secret`.');
    }
}
