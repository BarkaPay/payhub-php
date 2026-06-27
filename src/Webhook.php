<?php

declare(strict_types=1);

namespace PayHub;

use PayHub\Exception\SignatureVerificationException;
use PayHub\Util\Json;

/**
 * Verify the `PayHub-Signature` header on incoming webhooks.
 *
 * The header is `t=<unix_ts>,v1=<hex>` where `v1 = HMAC-SHA256("{t}.{rawBody}")`
 * keyed with your endpoint secret (`whsec_…`). Always verify against the RAW
 * request body — not a re-encoded copy.
 */
final class Webhook
{
    public const DEFAULT_TOLERANCE = 300;

    /**
     * @param string $payload         the raw request body
     * @param string $signatureHeader the `PayHub-Signature` header value
     * @param string $secret          your endpoint signing secret (`whsec_…`)
     * @param int    $tolerance       max clock skew in seconds (0 disables the check)
     */
    public static function verify(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
    ): bool {
        try {
            self::assert($payload, $signatureHeader, $secret, $tolerance);

            return true;
        } catch (SignatureVerificationException) {
            return false;
        }
    }

    /**
     * Verify and decode the webhook body in one step.
     *
     * @return array<string, mixed>
     *
     * @throws SignatureVerificationException
     */
    public static function parse(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
    ): array {
        self::assert($payload, $signatureHeader, $secret, $tolerance);

        $data = Json::decode($payload);
        if ($data === null) {
            throw new SignatureVerificationException('Webhook payload is not valid JSON.');
        }

        return $data;
    }

    /**
     * @throws SignatureVerificationException
     */
    private static function assert(string $payload, string $header, string $secret, int $tolerance): void
    {
        $parsed = self::parseHeader($header);
        if ($parsed === null) {
            throw new SignatureVerificationException('Malformed or missing PayHub-Signature header.');
        }

        [$timestamp, $signature] = $parsed;

        if ($tolerance > 0 && abs(time() - $timestamp) > $tolerance) {
            throw new SignatureVerificationException('Webhook timestamp is outside the tolerance window.');
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        if (!hash_equals($expected, $signature)) {
            throw new SignatureVerificationException('Webhook signature mismatch.');
        }
    }

    /**
     * @return array{0: int, 1: string}|null
     */
    private static function parseHeader(string $header): ?array
    {
        $timestamp = null;
        $signature = null;

        foreach (explode(',', $header) as $part) {
            $pair = explode('=', trim($part), 2);
            if (\count($pair) !== 2) {
                continue;
            }
            [$key, $value] = $pair;
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signature = $value;
            }
        }

        if ($timestamp === null || $signature === null || !ctype_digit($timestamp)) {
            return null;
        }

        return [(int) $timestamp, $signature];
    }
}
