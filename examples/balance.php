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
    $balance = $payhub->balance->get();

    if ($balance->balances !== null) {
        // Crypto (cc): one entry per asset/network.
        foreach ($balance->balances as $b) {
            printf("%s/%s: %s\n", $b['asset'] ?? '?', $b['network'] ?? '?', $b['available'] ?? '0');
        }
    } else {
        printf("Available: %s %s\n", $balance->available ?? '0', $balance->currency ?? '');
    }
} catch (PayHubException $e) {
    fwrite(STDERR, 'PayHub error: ' . $e->getMessage() . "\n");
}
