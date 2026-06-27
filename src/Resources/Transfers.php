<?php

declare(strict_types=1);

namespace PayHub\Resources;

use PayHub\ApiClient;
use PayHub\DTO\Transfer;
use PayHub\DTO\TransferCollection;
use PayHub\Exception\NotFoundException;
use PayHub\Util\Coerce;

final class Transfers
{
    public function __construct(private readonly ApiClient $api)
    {
    }

    /**
     * @param array<string, mixed> $params operator, phone_number, amount, order{…}, ignore_double_spend_risk?
     */
    public function create(array $params, ?string $country = null): Transfer
    {
        return Transfer::fromArray($this->unwrap($this->api->request('POST', 'transfers', $country, $params)));
    }

    /**
     * @param array<string, scalar|null> $filters
     */
    public function list(array $filters = [], ?string $country = null): TransferCollection
    {
        return TransferCollection::fromEnvelope(
            $this->api->request('GET', 'transfers', $country, null, $filters),
        );
    }

    /** Look up a transfer by its public_id (UUID) or your order_id. */
    public function get(string $id, ?string $country = null): Transfer
    {
        $env = $this->api->request('GET', 'transfers/' . rawurlencode($id), $country);
        $data = Coerce::arr($env, 'data') ?? [];
        $rows = Coerce::arr($data, 'transfers') ?? [];

        foreach ($rows as $row) {
            if (\is_array($row)) {
                /** @var array<string, mixed> $row */
                return Transfer::fromArray($row);
            }
        }

        throw new NotFoundException('No transfer found for "' . $id . '".', 'resource_not_found', 404);
    }

    /**
     * @param array<string, mixed> $env
     *
     * @return array<string, mixed>
     */
    private function unwrap(array $env): array
    {
        $data = Coerce::arr($env, 'data') ?? [];

        return Coerce::arr($data, 'transfer') ?? [];
    }
}
