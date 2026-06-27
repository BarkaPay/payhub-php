<?php

declare(strict_types=1);

namespace PayHub\DTO;

use PayHub\Enum\TransferStatus;
use PayHub\Util\Coerce;

/**
 * A disbursement (transfer) as returned by the API. Documented fields are
 * typed; `raw` keeps the full payload for forward-compatibility.
 */
final class Transfer
{
    /**
     * @param array<string, mixed>|null $orderData
     * @param array<string, mixed>      $raw
     */
    public function __construct(
        public readonly string $publicId,
        public readonly int|float|string|null $amount,
        public readonly ?string $fees,
        public readonly ?string $currency,
        public readonly ?string $phoneNumber,
        public readonly ?string $country,
        public readonly ?string $operator,
        public readonly ?string $channel,
        public readonly ?TransferStatus $status,
        public readonly ?string $orderId,
        public readonly ?string $providerTransactionId,
        public readonly ?array $orderData,
        public readonly ?string $reason,
        public readonly ?string $createdAt,
        public readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $statusValue = Coerce::str($data, 'status');

        return new self(
            publicId: Coerce::str($data, 'public_id') ?? '',
            amount: Coerce::amount($data, 'amount'),
            fees: Coerce::str($data, 'fees'),
            currency: Coerce::str($data, 'currency'),
            phoneNumber: Coerce::str($data, 'phone_number'),
            country: Coerce::str($data, 'country'),
            operator: Coerce::str($data, 'operator'),
            channel: Coerce::str($data, 'channel'),
            status: $statusValue !== null ? TransferStatus::tryFrom($statusValue) : null,
            orderId: Coerce::str($data, 'order_id'),
            providerTransactionId: Coerce::str($data, 'provider_transaction_id'),
            orderData: Coerce::arr($data, 'order_data'),
            reason: Coerce::str($data, 'reason'),
            createdAt: Coerce::str($data, 'created_at'),
            raw: $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }
}
