<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PayHub\Exception\SignatureVerificationException;
use PayHub\Webhook;

// Minimal webhook receiver. Mount this at your registered endpoint URL.
$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_PAYHUB_SIGNATURE'] ?? '';
$secret = getenv('PAYHUB_WEBHOOK_SECRET') ?: 'whsec_xxx';

try {
    // Verify against the RAW body before trusting anything.
    $event = Webhook::parse($payload, is_string($signature) ? $signature : '', $secret);
} catch (SignatureVerificationException) {
    http_response_code(400);
    exit;
}

// There is no `event` field — derive it from `type` + `status`.
$type = $event['type'] ?? null;
$status = $event['status'] ?? null;
$publicId = $event['public_id'] ?? null;

// TODO: deduplicate on $publicId (retries deliver the same body) and update your order.
// e.g. ($type === 'payment' && $status === 'SUCCESSFUL') => mark paid.

http_response_code(200);
echo 'ok';
