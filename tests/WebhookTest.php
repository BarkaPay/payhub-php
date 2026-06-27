<?php

declare(strict_types=1);

namespace PayHub\Tests;

use PayHub\Exception\SignatureVerificationException;
use PayHub\Webhook;
use PHPUnit\Framework\TestCase;

final class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';
    private const PAYLOAD = '{"type":"payment","public_id":"pay_1","status":"SUCCESSFUL"}';

    private function sign(string $payload, int $timestamp, string $secret = self::SECRET): string
    {
        $sig = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        return 't=' . $timestamp . ',v1=' . $sig;
    }

    public function testVerifyAndParseValidSignature(): void
    {
        $header = $this->sign(self::PAYLOAD, time());

        self::assertTrue(Webhook::verify(self::PAYLOAD, $header, self::SECRET));

        $event = Webhook::parse(self::PAYLOAD, $header, self::SECRET);
        self::assertSame('payment', $event['type']);
        self::assertSame('SUCCESSFUL', $event['status']);
    }

    public function testRejectsTamperedPayload(): void
    {
        $header = $this->sign(self::PAYLOAD, time());

        self::assertFalse(Webhook::verify('{"type":"payment","amount":"999"}', $header, self::SECRET));
    }

    public function testRejectsWrongSecret(): void
    {
        $header = $this->sign(self::PAYLOAD, time());

        self::assertFalse(Webhook::verify(self::PAYLOAD, $header, 'whsec_other'));
    }

    public function testRejectsStaleTimestamp(): void
    {
        $header = $this->sign(self::PAYLOAD, time() - 1000);

        self::assertFalse(Webhook::verify(self::PAYLOAD, $header, self::SECRET));
    }

    public function testRejectsMalformedHeader(): void
    {
        self::assertFalse(Webhook::verify(self::PAYLOAD, 'not-a-signature', self::SECRET));
    }

    public function testParseThrowsOnBadSignature(): void
    {
        $this->expectException(SignatureVerificationException::class);
        Webhook::parse(self::PAYLOAD, $this->sign(self::PAYLOAD, time()), 'whsec_other');
    }
}
