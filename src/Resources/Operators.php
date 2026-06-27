<?php

declare(strict_types=1);

namespace PayHub\Resources;

use PayHub\ApiClient;
use PayHub\Util\Coerce;

final class Operators
{
    public function __construct(private readonly ApiClient $api)
    {
    }

    /**
     * Real-time payment/transfer availability per operator.
     *
     * @return array<string, mixed>
     */
    public function availability(?string $country = null): array
    {
        return Coerce::arr($this->api->request('GET', 'operators/availability', $country), 'data') ?? [];
    }

    /**
     * Operator capabilities (OTP requirement, amount bounds, instructions). This
     * is the authoritative list of operators for the country.
     *
     * @return array<string, mixed>
     */
    public function info(?string $country = null): array
    {
        return Coerce::arr($this->api->request('GET', 'operators/info', $country), 'data') ?? [];
    }
}
