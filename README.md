# PayHub PHP SDK

Official PHP client for the [PayHub](https://payhub.africa/developers) payments API —
mobile money & crypto, one API across countries.

- Zero dependencies (just `ext-curl` + `ext-json`) — safe to embed inside WordPress / WooCommerce.
- Typed payments, transfers and balance objects.
- Built-in webhook signature verification.
- Automatic retries on transient errors.

## Requirements

PHP **8.1+** with the `curl` and `json` extensions.

## Install

```bash
composer require barkapay/payhub-php
```

## Quick start

```php
use PayHub\Client;

$payhub = new Client(
    apiKey:  'pk_live_xxx:sk_live_yyy', // the "key_id:secret" you copy from the dashboard
    country: 'bf',                       // default country, overridable per call
);

$payment = $payhub->payments->create([
    'operator'     => 'ORANGE',
    'phone_number' => '50123456789',
    'amount'       => 10000,
    'otp'          => '123456',          // only for synchronous-OTP operators (e.g. Orange)
    'order'        => ['id' => 'ORDER-2026-001'],
]);

echo $payment->publicId, ' ', $payment->status?->value;
```

> **Authentication:** pass the raw `key_id:secret` (or `keyId` + `secret` separately).
> The SDK adds the `Bearer` prefix — never include the word `Bearer` yourself.

## Payments

```php
$payhub->payments->create([...]);                 // returns PayHub\DTO\Payment
$payhub->payments->get('pay_… or order_id');      // by public_id or your order_id
$payhub->payments->list(['status' => 'SUCCESSFUL', 'per_page' => 50]); // PaymentCollection
$payhub->payments->confirmOtp($publicId, '123456'); // for AWAITING_OTP payments
$payhub->payments->resendOtp($publicId);
```

The flow depends on the operator — always branch on the returned `status`:
synchronous (`SUCCESSFUL`/`FAILED`), `AWAITING_OTP` (confirm step), or
`PROCESSING_OPERATOR` (final outcome arrives by webhook).

## Transfers

```php
$payhub->transfers->create([
    'operator'     => 'ORANGE',
    'phone_number' => '50123456789',
    'amount'       => 50000,
    'order'        => ['id' => 'XFER-2026-001'],
]);
$payhub->transfers->get($id);
$payhub->transfers->list(['from_date' => '2026-06-01']);
```

## Balance & operators

```php
$balance = $payhub->balance->get();   // PayHub\DTO\Balance: available / total / holds / currency
$payhub->operators->info();           // authoritative operator list for the country
$payhub->operators->availability();
$payhub->me();
```

## Webhooks

Verify the `PayHub-Signature` header against the **raw** request body:

```php
use PayHub\Webhook;
use PayHub\Exception\SignatureVerificationException;

try {
    $event = Webhook::parse(
        $rawBody,
        $_SERVER['HTTP_PAYHUB_SIGNATURE'] ?? '',
        $endpointSecret, // whsec_…
    );
} catch (SignatureVerificationException) {
    http_response_code(400);
    exit;
}

// No `event` field — derive it from $event['type'] + $event['status'].
// Deduplicate on $event['public_id'] (retries deliver the same body).
```

## Errors

Every API error throws a typed exception extending `PayHub\Exception\ApiException`
(`getErrorCode()`, `getHttpStatus()`, `getRequestId()`, `getErrors()`):

| Exception | When |
|---|---|
| `AuthenticationException` | 401 — bad credentials |
| `AuthorizationException`  | 403/410/451 — not allowed |
| `ValidationException`     | 422 — bad request (`getErrors()`) |
| `NotFoundException`       | 404 |
| `ConflictException`       | 409 — duplicate |
| `RateLimitException`      | 429 |
| `ServiceUnavailableException` | 503 — retryable |
| `NetworkException`        | request never reached a response |

Catch everything from the SDK with `PayHub\Exception\PayHubException`.

## Configuration

```php
new Client(
    apiKey:     'key_id:secret',
    country:    'bf',
    baseUrl:    'https://hub.barkapay.com',
    maxRetries: 2,            // 429/503/network
    timeout:    30.0,         // seconds
    httpClient: $custom,      // any PayHub\Http\HttpClientInterface
);
```

## License

MIT.
