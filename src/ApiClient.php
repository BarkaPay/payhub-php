<?php

declare(strict_types=1);

namespace PayHub;

use PayHub\Exception\ApiException;
use PayHub\Exception\ConfigurationException;
use PayHub\Exception\NetworkException;
use PayHub\Http\HttpClientInterface;
use PayHub\Http\Request;
use PayHub\Http\Response;
use PayHub\Util\Json;
use PayHub\Util\RequestId;

/**
 * Internal transport: turns a logical call (method, path, country, body) into a
 * signed HTTP request against `https://hub.barkapay.com/{country}/v1/…`, retries
 * transient failures, and unwraps the standard response envelope (throwing a
 * typed {@see ApiException} on error).
 *
 * @internal
 */
final class ApiClient
{
    private const BASE_DELAY_MS = 300;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $authorization,
        private readonly string $baseUrl,
        private readonly string $defaultCountry,
        private readonly int $maxRetries,
        private readonly string $userAgent,
    ) {
    }

    /**
     * @param array<string, mixed>|null            $body
     * @param array<string, scalar|null>|null      $query
     *
     * @return array<string, mixed> the decoded response envelope
     */
    public function request(
        string $method,
        string $path,
        ?string $country = null,
        ?array $body = null,
        ?array $query = null,
    ): array {
        $cc = strtolower($country ?? $this->defaultCountry);
        if ($cc === '') {
            throw new ConfigurationException('No country given for this call and no default set on the client.');
        }

        $url = $this->baseUrl . '/' . rawurlencode($cc) . '/v1/' . ltrim($path, '/');
        if ($query !== null) {
            $filtered = array_filter($query, static fn ($v): bool => $v !== null);
            if ($filtered !== []) {
                $url .= '?' . http_build_query($filtered);
            }
        }

        $requestId = RequestId::generate();
        $headers = [
            'Authorization' => $this->authorization,
            'Accept' => 'application/json',
            'User-Agent' => $this->userAgent,
            'X-Request-Id' => $requestId,
        ];

        $payload = null;
        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
            $payload = Json::encode($body);
        }

        $request = new Request($method, $url, $headers, $payload);

        $attempt = 0;
        while (true) {
            try {
                $response = $this->httpClient->send($request);
            } catch (NetworkException $e) {
                if ($attempt < $this->maxRetries) {
                    $this->backoff($attempt++);

                    continue;
                }

                throw $e;
            }

            if ($this->shouldRetry($response->statusCode) && $attempt < $this->maxRetries) {
                $this->backoff($attempt++);

                continue;
            }

            return $this->handle($response, $requestId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function handle(Response $response, string $requestId): array
    {
        $envelope = Json::decode($response->body) ?? [];
        $status = $response->statusCode;
        $envStatus = $envelope['status'] ?? null;

        if ($status >= 400 || $envStatus === 'error' || $envStatus === 'failed') {
            if (!\array_key_exists('request_id', $envelope)) {
                $envelope['request_id'] = $requestId;
            }

            throw ApiException::fromEnvelope($status, $envelope);
        }

        return $envelope;
    }

    private function shouldRetry(int $status): bool
    {
        return $status === 429 || $status === 503;
    }

    private function backoff(int $attempt): void
    {
        usleep(self::BASE_DELAY_MS * (2 ** $attempt) * 1000);
    }
}
