<?php

declare(strict_types=1);

namespace PayHub\Resources;

use PayHub\ApiClient;
use PayHub\DTO\Balance as BalanceDto;
use PayHub\Util\Coerce;

final class Balance
{
    public function __construct(private readonly ApiClient $api)
    {
    }

    /** The merchant's wallet balance for the country. */
    public function get(?string $country = null): BalanceDto
    {
        $env = $this->api->request('GET', 'balance', $country);

        return BalanceDto::fromArray(Coerce::arr($env, 'data') ?? []);
    }
}
