<?php

declare(strict_types=1);

namespace PayHub\DTO;

use PayHub\Util\Coerce;

/**
 * The merchant wallet balance for a country.
 *
 * Fiat wallets fill `currency` + `available`/`total`/`holds` (decimal strings).
 * Crypto (`cc`) instead fills `balances`, one entry per asset/network.
 */
final class Balance
{
    /**
     * @param list<array<string, mixed>>|null $balances
     * @param array<string, mixed>            $raw
     */
    public function __construct(
        public readonly ?string $country,
        public readonly ?string $currency,
        public readonly ?string $available,
        public readonly ?string $total,
        public readonly ?string $holds,
        public readonly ?array $balances,
        public readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $balancesRaw = Coerce::arr($data, 'balances');
        $balances = null;
        if ($balancesRaw !== null) {
            $balances = [];
            foreach ($balancesRaw as $entry) {
                if (\is_array($entry)) {
                    /** @var array<string, mixed> $entry */
                    $balances[] = $entry;
                }
            }
        }

        return new self(
            country: Coerce::str($data, 'country'),
            currency: Coerce::str($data, 'currency'),
            available: Coerce::str($data, 'available'),
            total: Coerce::str($data, 'total'),
            holds: Coerce::str($data, 'holds'),
            balances: $balances,
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
