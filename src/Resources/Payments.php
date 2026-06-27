<?php

declare(strict_types=1);

namespace PayHub\Resources;

use PayHub\ApiClient;
use PayHub\DTO\Payment;
use PayHub\DTO\PaymentCollection;
use PayHub\Exception\NotFoundException;
use PayHub\Util\Coerce;

final class Payments
{
    public function __construct(private readonly ApiClient $api)
    {
    }

    /**
     * @param array<string, mixed> $params operator, phone_number, amount, order{…}, otp?
     */
    public function create(array $params, ?string $country = null): Payment
    {
        return Payment::fromArray($this->unwrap($this->api->request('POST', 'payments', $country, $params)));
    }

    /**
     * @param array<string, scalar|null> $filters status, operator, from_date, to_date, per_page, …
     */
    public function list(array $filters = [], ?string $country = null): PaymentCollection
    {
        return PaymentCollection::fromEnvelope(
            $this->api->request('GET', 'payments', $country, null, $filters),
        );
    }

    /** Look up a payment by its public_id (UUID) or your order_id. */
    public function get(string $id, ?string $country = null): Payment
    {
        $env = $this->api->request('GET', 'payments/' . rawurlencode($id), $country);
        $data = Coerce::arr($env, 'data') ?? [];
        $rows = Coerce::arr($data, 'payments') ?? [];

        foreach ($rows as $row) {
            if (\is_array($row)) {
                /** @var array<string, mixed> $row */
                return Payment::fromArray($row);
            }
        }

        throw new NotFoundException('No payment found for "' . $id . '".', 'resource_not_found', 404);
    }

    /** Confirm a payment that is AWAITING_OTP (operators that require an OTP step). */
    public function confirmOtp(string $publicId, string $otp, ?string $country = null): Payment
    {
        $env = $this->api->request(
            'POST',
            'payments/' . rawurlencode($publicId) . '/confirm-otp',
            $country,
            ['otp' => $otp],
        );

        return Payment::fromArray($this->unwrap($env));
    }

    public function resendOtp(string $publicId, ?string $country = null): void
    {
        $this->api->request('POST', 'payments/' . rawurlencode($publicId) . '/resend-otp', $country);
    }

    /**
     * @param array<string, mixed> $env
     *
     * @return array<string, mixed>
     */
    private function unwrap(array $env): array
    {
        $data = Coerce::arr($env, 'data') ?? [];

        return Coerce::arr($data, 'payment') ?? [];
    }
}
