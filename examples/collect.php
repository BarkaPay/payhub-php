<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PayHub\Client;
use PayHub\Exception\PayHubException;

$payhub = new Client(
    apiKey: getenv('PAYHUB_API_KEY') ?: 'pk_live_xxx:sk_live_yyy',
    country: 'bf',
);

try {
    // The OTP field is only needed for synchronous-OTP operators (e.g. Orange);
    // most async operators (MTN, Wave) return PROCESSING_OPERATOR and complete
    // via webhook. Always branch on the returned status.
    $payment = $payhub->payments->create([
        'operator' => 'ORANGE',
        'phone_number' => '50123456789',
        'amount' => 10000,
        'otp' => '123456',
        'order' => ['id' => 'ORDER-' . time()],
    ]);

    printf("Payment %s — status %s\n", $payment->publicId, $payment->status?->value ?? 'unknown');
} catch (PayHubException $e) {
    fwrite(STDERR, 'PayHub error: ' . $e->getMessage() . "\n");
}
