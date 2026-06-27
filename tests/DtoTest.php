<?php

declare(strict_types=1);

namespace PayHub\Tests;

use PayHub\DTO\Balance;
use PayHub\DTO\Payment;
use PayHub\Enum\PaymentStatus;
use PayHub\Enum\TransferStatus;
use PHPUnit\Framework\TestCase;

final class DtoTest extends TestCase
{
    public function testPaymentHydratesAndKeepsRaw(): void
    {
        $payment = Payment::fromArray([
            'public_id' => 'pay_9',
            'amount' => 10000,
            'fees' => '250.00',
            'currency' => 'XOF',
            'status' => 'AWAITING_OTP',
            'order_id' => 'O-1',
            'future_field' => 'kept',
        ]);

        self::assertSame('pay_9', $payment->publicId);
        self::assertSame(10000, $payment->amount);
        self::assertSame('250.00', $payment->fees);
        self::assertSame(PaymentStatus::AwaitingOtp, $payment->status);
        self::assertSame('O-1', $payment->orderId);
        self::assertNull($payment->reason);
        self::assertSame('kept', $payment->toArray()['future_field']);
    }

    public function testPaymentUnknownStatusBecomesNull(): void
    {
        $payment = Payment::fromArray(['public_id' => 'p', 'status' => 'SOMETHING_NEW']);

        self::assertNull($payment->status);
    }

    public function testBalanceFiatAndCryptoShapes(): void
    {
        $fiat = Balance::fromArray(['country' => 'BF', 'currency' => 'XOF', 'available' => '1500.00']);
        self::assertSame('1500.00', $fiat->available);
        self::assertNull($fiat->balances);

        $crypto = Balance::fromArray([
            'country' => 'CC',
            'balances' => [
                ['asset' => 'USDT', 'network' => 'TRC20', 'available' => '12.50'],
            ],
        ]);
        self::assertIsArray($crypto->balances);
        self::assertCount(1, $crypto->balances);
        self::assertSame('USDT', $crypto->balances[0]['asset']);
    }

    public function testStatusFinality(): void
    {
        self::assertTrue(PaymentStatus::Successful->isFinal());
        self::assertFalse(PaymentStatus::ProcessingOperator->isFinal());
        self::assertTrue(TransferStatus::Refunded->isFinal());
        self::assertFalse(TransferStatus::Created->isFinal());
    }
}
