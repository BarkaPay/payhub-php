<?php

declare(strict_types=1);

namespace PayHub\Http;

use PayHub\Exception\NetworkException;

/**
 * Zero-dependency HTTP client built on ext-curl. No Guzzle, no PSR packages —
 * safe to embed inside WordPress / WooCommerce where dependency versions clash.
 */
final class CurlClient implements HttpClientInterface
{
    public function __construct(
        private readonly float $timeout = 30.0,
        private readonly float $connectTimeout = 10.0,
    ) {
    }

    public function send(Request $request): Response
    {
        $handle = curl_init();
        if ($handle === false) {
            throw new NetworkException('Unable to initialise curl.');
        }

        $headers = [];
        foreach ($request->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        curl_setopt_array($handle, [
            CURLOPT_URL => $request->url,
            CURLOPT_CUSTOMREQUEST => $request->method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT_MS => (int) ($this->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) ($this->connectTimeout * 1000),
        ]);

        if ($request->body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $request->body);
        }

        $raw = curl_exec($handle);

        if ($raw === false || $raw === true) {
            $error = curl_error($handle);
            $errno = curl_errno($handle);
            curl_close($handle);

            throw new NetworkException(
                $error !== '' ? $error : 'HTTP request failed.',
                $errno,
            );
        }

        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        curl_close($handle);

        $rawHeaders = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        return new Response($status, $body, $this->parseHeaders($rawHeaders));
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(string $raw): array
    {
        $headers = [];
        // Keep only the final header block (in case an intermediary added one).
        $blocks = preg_split("/\r?\n\r?\n/", trim($raw)) ?: [];
        $last = (string) end($blocks);

        foreach (preg_split("/\r?\n/", $last) ?: [] as $line) {
            $parts = explode(':', $line, 2);
            if (\count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }

        return $headers;
    }
}
