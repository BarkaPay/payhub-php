<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PayHub\Client;
use PayHub\Exception\ConflictException;
use PayHub\Exception\PayHubException;

$payhub = new Client(
    apiKey: getenv('PAYHUB_API_KEY') ?: 'pk_live_xxx:sk_live_yyy',
    country: 'bf',
);

try {
    $transfer = $payhub->transfers->create([
        'operator' => 'ORANGE',
        'phone_number' => '50123456789',
        'amount' => 50000,
        'order' => ['id' => 'XFER-' . time()],
    ]);

    printf("Transfer %s — status %s\n", $transfer->publicId, $transfer->status?->value ?? 'unknown');
} catch (ConflictException $e) {
    // The 60s duplicate guard fired — a near-identical transfer is already in flight.
    fwrite(STDERR, 'Duplicate transfer: ' . $e->getMessage() . "\n");
} catch (PayHubException $e) {
    fwrite(STDERR, 'PayHub error: ' . $e->getMessage() . "\n");
}
