<?php

declare(strict_types=1);

namespace PayHub\Http;

/**
 * An outbound HTTP request. Plain value object so a custom
 * {@see HttpClientInterface} can be plugged in without depending on PSR types.
 */
final class Request
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly array $headers = [],
        public readonly ?string $body = null,
    ) {
    }
}
