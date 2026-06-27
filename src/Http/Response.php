<?php

declare(strict_types=1);

namespace PayHub\Http;

/**
 * A raw HTTP response returned by an {@see HttpClientInterface}.
 */
final class Response
{
    /**
     * @param array<string, string> $headers lower-cased header name => value
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
        public readonly array $headers = [],
    ) {
    }
}
